<?php

namespace Tests\Feature\Admin;

use App\Enums\MediaRole;
use App\Enums\Role;
use App\Models\Media;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\TeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_cover_upload_is_reencoded_to_webp_variants_and_replaces_previous_cover(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $project = Project::factory()->create();

        $this->post("/admin/projects/{$project->id}/cover", ['cover' => UploadedFile::fake()->image('Cover Photo.jpg', 1600, 1000), 'cover_alt' => 'Dashboard'])->assertSessionHasNoErrors();
        $first = $project->cover()->first()->media;

        $this->assertSame('image/webp', $first->mime_type);
        $this->assertEquals([480, 960, 1600], array_keys($first->variants));
        foreach ($first->variants as $path) {
            Storage::disk('public')->assertExists($path);
            $this->assertStringEndsWith('.webp', $path);
            $this->assertStringNotContainsString('Cover', $path, 'Client file names must not be used in paths.');
        }

        $this->post("/admin/projects/{$project->id}/cover", ['cover' => UploadedFile::fake()->image('b.png', 1300, 800), 'cover_alt' => 'New'])->assertSessionHasNoErrors();
        $this->assertSame(1, $project->projectMedia()->where('role', MediaRole::Cover->value)->count());
        $this->assertFalse($first->fresh()->isReferenced());

        $this->travel(5)->minutes();
        $this->artisan('brivia:media-cleanup', ['--older-than' => 1])->assertSuccessful();
        $this->assertModelMissing($first);
        Storage::disk('public')->assertMissing($first->storage_path);
    }

    public function test_dangerous_and_invalid_files_are_rejected(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $project = Project::factory()->create();
        $post = fn ($file) => $this->post("/admin/projects/{$project->id}/cover", ['cover' => $file, 'cover_alt' => 'x']);

        $post(UploadedFile::fake()->createWithContent('shell.php.jpg', '<?php echo 1;'))->assertSessionHasErrors('cover');
        $post(UploadedFile::fake()->createWithContent('fake.jpg', 'not an image at all'))->assertSessionHasErrors('cover');
        $post(UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'))->assertSessionHasErrors('cover');
        $post(UploadedFile::fake()->image('anim.gif', 1600, 1000))->assertSessionHasErrors('cover');
        $post(UploadedFile::fake()->image('small.jpg', 400, 300))->assertSessionHasErrors('cover');
        $post(UploadedFile::fake()->image('huge.png', 6100, 700))->assertSessionHasErrors('cover');
        $post(UploadedFile::fake()->image('big.jpg', 1600, 1000)->size(6000))->assertSessionHasErrors('cover');

        $this->assertSame(0, Media::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_gallery_is_capped_at_twelve_images(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        config(['brivia.media.max_gallery_images' => 2]);
        $project = Project::factory()->create();

        foreach ([1, 2] as $i) {
            $this->post("/admin/projects/{$project->id}/gallery", ['gallery_image' => UploadedFile::fake()->image("g{$i}.jpg", 900, 600), 'gallery_alt' => "G{$i}"])->assertSessionHasNoErrors();
        }
        $this->post("/admin/projects/{$project->id}/gallery", ['gallery_image' => UploadedFile::fake()->image('g3.jpg', 900, 600), 'gallery_alt' => 'G3'])->assertSessionHasErrors('gallery_image');
        $this->assertSame(2, $project->gallery()->count());
    }

    public function test_project_publication_requires_permission_cover_and_alt_text(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $project = Project::factory()->create(['permission_confirmed' => false]);

        $this->post("/admin/projects/{$project->id}/publish");
        $problems = session('publication_problems');
        $this->assertContains('Confirm that BRIVIA has permission to publish this project.', $problems);
        $this->assertContains('Upload a cover image.', $problems);

        $project->forceFill(['permission_confirmed' => true])->save();
        $this->post("/admin/projects/{$project->id}/cover", ['cover' => UploadedFile::fake()->image('c.jpg', 1600, 1000), 'cover_alt' => 'Cover']);
        $this->post("/admin/projects/{$project->id}/publish")->assertSessionMissing('publication_problems');
        $this->assertTrue($project->fresh()->isPublished());

        $link = $project->cover()->first();
        $this->delete("/admin/projects/{$project->id}/media/{$link->id}")->assertSessionHas('error');
        $this->assertModelExists($link);
    }

    public function test_media_from_another_project_cannot_be_removed_through_this_one(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $a = Project::factory()->create();
        $b = Project::factory()->create();
        $this->post("/admin/projects/{$b->id}/gallery", ['gallery_image' => UploadedFile::fake()->image('g.jpg', 900, 600), 'gallery_alt' => 'G']);
        $link = $b->gallery()->first();

        $this->delete("/admin/projects/{$a->id}/media/{$link->id}")->assertNotFound();
        $this->put("/admin/projects/{$a->id}/media", ['media' => [$link->id => ['alt_text' => 'x', 'sort_order' => 1]]])->assertSessionHasErrors('media');
    }

    public function test_project_website_must_be_http_url(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $category = ProjectCategory::factory()->create();

        $this->post('/admin/projects', [
            'title' => 'X', 'category_id' => $category->id, 'summary' => 'S', 'work_origin' => 'concept', 'sort_order' => 1,
            'website_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('website_url');
    }

    public function test_published_project_cannot_drop_permission(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $project = Project::factory()->published()->create();

        $this->put("/admin/projects/{$project->id}", [
            'title' => $project->title, 'slug' => $project->slug, 'category_id' => $project->category_id, 'summary' => 'S',
            'work_origin' => 'concept', 'sort_order' => 1, 'permission_confirmed' => '0',
        ])->assertSessionHasErrors('permission_confirmed');
    }

    public function test_portrait_requires_approval_confirmation(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $member = TeamMember::factory()->create();

        $this->post("/admin/team/{$member->id}/portrait", ['portrait' => UploadedFile::fake()->image('p.jpg', 500, 500), 'portrait_alt' => 'Portrait'])->assertSessionHasErrors('portrait_approved');
        $this->post("/admin/team/{$member->id}/portrait", ['portrait' => UploadedFile::fake()->image('p.jpg', 500, 500), 'portrait_alt' => 'Portrait', 'portrait_approved' => '1'])->assertSessionHasNoErrors();
        $this->assertNotNull($member->fresh()->portrait_media_id);
    }
}
