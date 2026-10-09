<?php

use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ConsultationTypeController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\LegalPageController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\ProjectCategoryController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SingletonContentController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TeamMemberController;
use Illuminate\Support\Facades\Route;

/** Standard content resource routes: list, create, edit, delete, publish, unpublish, preview, reorder. */
$content = function (string $uri, string $controller, string $param, bool $preview = true) {
    Route::post("{$uri}/reorder", [$controller, 'sort'])->name("{$uri}.reorder");
    Route::get($uri, [$controller, 'index'])->name("{$uri}.index");
    Route::get("{$uri}/create", [$controller, 'create'])->name("{$uri}.create");
    Route::post($uri, [$controller, 'store'])->name("{$uri}.store");
    Route::get("{$uri}/{{$param}}/edit", [$controller, 'edit'])->name("{$uri}.edit");
    Route::put("{$uri}/{{$param}}", [$controller, 'update'])->name("{$uri}.update");
    Route::delete("{$uri}/{{$param}}", [$controller, 'destroy'])->name("{$uri}.destroy");
    Route::post("{$uri}/{{$param}}/publish", [$controller, 'publish'])->name("{$uri}.publish");
    Route::post("{$uri}/{{$param}}/unpublish", [$controller, 'unpublish'])->name("{$uri}.unpublish");
    if ($preview) {
        Route::get("{$uri}/{{$param}}/preview", [$controller, 'preview'])->name("{$uri}.preview");
    }
};

Route::middleware('can:manage-content')->group(function () use ($content) {
    $content('services', ServiceController::class, 'service');
    $content('packages', PackageController::class, 'package');
    $content('team', TeamMemberController::class, 'teamMember');
    $content('consultation-types', ConsultationTypeController::class, 'consultationType', false);

    Route::get('project-categories', [ProjectCategoryController::class, 'index'])->name('project-categories.index');
    Route::post('project-categories', [ProjectCategoryController::class, 'store'])->name('project-categories.store');
    Route::put('project-categories/{category}', [ProjectCategoryController::class, 'update'])->name('project-categories.update');
    Route::delete('project-categories/{category}', [ProjectCategoryController::class, 'destroy'])->name('project-categories.destroy');

    $content('projects', ProjectController::class, 'project');
    Route::post('projects/{project}/cover', [ProjectController::class, 'uploadCover'])->name('projects.cover');
    Route::post('projects/{project}/gallery', [ProjectController::class, 'uploadGallery'])->name('projects.gallery');
    Route::put('projects/{project}/media', [ProjectController::class, 'updateMedia'])->name('projects.media.update');
    Route::delete('projects/{project}/media/{projectMedia}', [ProjectController::class, 'removeMedia'])->name('projects.media.destroy');

    Route::post('team/{teamMember}/portrait', [TeamMemberController::class, 'uploadPortrait'])->name('team.portrait');
    Route::delete('team/{teamMember}/portrait', [TeamMemberController::class, 'removePortrait'])->name('team.portrait.destroy');
    Route::post('team/{teamMember}/approve', [TeamMemberController::class, 'approvePlaceholder'])->name('team.approve');

    Route::get('about', [SingletonContentController::class, 'editAbout'])->name('about.edit');
    Route::put('about', [SingletonContentController::class, 'updateAbout'])->name('about.update');
    Route::get('homepage', [SingletonContentController::class, 'editHomepage'])->name('homepage.edit');
    Route::put('homepage', [SingletonContentController::class, 'updateHomepage'])->name('homepage.update');
    Route::get('settings', [SingletonContentController::class, 'editSettings'])->name('settings.edit');
    Route::put('settings', [SingletonContentController::class, 'updateSettings'])->name('settings.update');

    Route::get('legal', [LegalPageController::class, 'index'])->name('legal.index');
    Route::get('legal/{legalPage}/edit', [LegalPageController::class, 'edit'])->name('legal.edit');
    Route::put('legal/{legalPage}', [LegalPageController::class, 'update'])->name('legal.update');
    Route::post('legal/{legalPage}/publish', [LegalPageController::class, 'publish'])->name('legal.publish');
    Route::post('legal/{legalPage}/unpublish', [LegalPageController::class, 'unpublish'])->name('legal.unpublish');
    Route::post('legal/{legalPage}/approve', [LegalPageController::class, 'approvePlaceholder'])->name('legal.approve');
    Route::get('legal/{legalPage}/preview', [LegalPageController::class, 'preview'])->name('legal.preview');
});

Route::middleware('can:manage-operations')->group(function () {
    Route::get('enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
    Route::get('enquiries/{enquiry}', [EnquiryController::class, 'show'])->name('enquiries.show');
    Route::post('enquiries/{enquiry}/status', [EnquiryController::class, 'status'])->name('enquiries.status');
    Route::post('enquiries/{enquiry}/assign', [EnquiryController::class, 'assign'])->name('enquiries.assign');
    Route::post('enquiries/{enquiry}/notes', [EnquiryController::class, 'note'])->name('enquiries.notes');
    Route::delete('enquiries/{enquiry}', [EnquiryController::class, 'destroy'])->name('enquiries.destroy');

    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::post('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::post('appointments/{appointment}/decline', [AppointmentController::class, 'decline'])->name('appointments.decline');
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('appointments/{appointment}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('appointments/{appointment}/assign', [AppointmentController::class, 'assign'])->name('appointments.assign');
    Route::post('appointments/{appointment}/notes', [AppointmentController::class, 'note'])->name('appointments.notes');
    Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{delivery}/retry', [NotificationController::class, 'retry'])->name('notifications.retry');
});

Route::middleware('can:manage-staff')->group(function () {
    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('staff/invitations', [StaffController::class, 'invite'])->middleware('throttle:admin-invitations')->name('staff.invite');
    Route::delete('staff/invitations/{invitation}', [StaffController::class, 'revokeInvitation'])->name('staff.invitations.revoke');
    Route::put('staff/{user}/role', [StaffController::class, 'updateRole'])->name('staff.role');
    Route::post('staff/{user}/deactivate', [StaffController::class, 'deactivate'])->name('staff.deactivate');
    Route::post('staff/{user}/reactivate', [StaffController::class, 'reactivate'])->name('staff.reactivate');
});

Route::middleware('can:view-audit-log')->group(function () {
    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');
});
