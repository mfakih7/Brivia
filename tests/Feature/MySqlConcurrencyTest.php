<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ConsultationType;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Real-MySQL concurrency check (spec 05): two separate PHP processes confirm overlapping times
 * for the same assignee at the same moment. Exactly one must win. Opt-in, because it needs a
 * SEPARATE MySQL/MariaDB test database (never the application database):
 *
 *   BRIVIA_TEST_MYSQL_DATABASE=brivia_test php artisan test --filter=MySqlConcurrencyTest
 */
class MySqlConcurrencyTest extends TestCase
{
    private string $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = (string) env('BRIVIA_TEST_MYSQL_DATABASE', '');
        if ($this->database === '') {
            $this->markTestSkipped('Set BRIVIA_TEST_MYSQL_DATABASE to a dedicated MySQL test database to run this test.');
        }
        if (! str_ends_with($this->database, '_test') || $this->database === env('DB_DATABASE_APP', 'brivia')) {
            $this->fail('Refusing to run: the MySQL test database name must end in "_test" and differ from the application database.');
        }

        config(['database.connections.mysql_test' => array_merge(config('database.connections.mysql'), [
            'host' => env('BRIVIA_TEST_MYSQL_HOST', '127.0.0.1'),
            'port' => env('BRIVIA_TEST_MYSQL_PORT', '3306'),
            'database' => $this->database,
            'username' => env('BRIVIA_TEST_MYSQL_USERNAME', 'root'),
            'password' => env('BRIVIA_TEST_MYSQL_PASSWORD', ''),
        ]), 'database.default' => 'mysql_test']);
        DB::purge('mysql_test');

        Artisan::call('migrate', ['--database' => 'mysql_test', '--force' => true]); // additive only, never fresh
    }

    public function test_simultaneous_overlapping_confirmations_produce_exactly_one_booking(): void
    {
        $assignee = User::factory()->operationsManager()->create(['email' => 'concurrency-'.uniqid().'@example.test']);
        $type = ConsultationType::factory()->published()->create(['slug' => 'concurrency-'.uniqid()]);
        [$a, $b] = Appointment::factory()->count(2)->create(['consultation_type_id' => $type->id]);
        $start = now()->addDays(5)->setTime(10, 0)->format('Y-m-d H:i:s');

        $env = [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => config('database.connections.mysql_test.host'),
            'DB_PORT' => (string) config('database.connections.mysql_test.port'), 'DB_DATABASE' => $this->database,
            'DB_USERNAME' => config('database.connections.mysql_test.username'), 'DB_PASSWORD' => (string) config('database.connections.mysql_test.password'),
            'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
            'BRIVIA_TEST_LOCK_DELAY_MS' => '1500',
        ];
        $script = base_path('tests/Support/confirm-appointment.php');
        $processes = collect([[$a->id, '10:00'], [$b->id, '10:30']])->map(fn ($args) => new Process(
            [PHP_BINARY, $script, $args[0], $assignee->id, $start, $args[1]], base_path(), $env, null, 60,
        ));

        try {
            $processes->each->start();
            $outputs = $processes->map(function (Process $p) {
                $p->wait();

                return trim($p->getOutput().$p->getErrorOutput());
            });

            $results = $outputs->map(fn ($o) => str_starts_with($o, 'confirmed') ? 'confirmed' : (str_starts_with($o, 'conflict') ? 'conflict' : $o))->sort()->values()->all();
            $this->assertSame(['confirmed', 'conflict'], $results, 'Process output: '.$outputs->implode(' | '));
            $this->assertSame(1, Appointment::whereIn('id', [$a->id, $b->id])->where('status', AppointmentStatus::Confirmed->value)->count());
        } finally {
            Appointment::whereIn('id', [$a->id, $b->id])->delete();
            $type->delete();
            $assignee->delete();
        }
    }
}
