<?php

use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\ConsultationController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LegalController;
use App\Http\Controllers\Public\PackagePageController;
use App\Http\Controllers\Public\ProjectPageController;
use App\Http\Controllers\Public\SeoController;
use App\Http\Controllers\Public\ServicePageController;
use Illuminate\Support\Facades\Route;

$slug = '[a-z0-9]+(?:-[a-z0-9]+)*';

Route::get('/', HomeController::class)->name('home');
Route::get('/services', [ServicePageController::class, 'index'])->name('services.index');
Route::get('/services/{slug}', [ServicePageController::class, 'show'])->where('slug', $slug)->name('services.show');
Route::get('/packages', PackagePageController::class)->name('packages');
Route::get('/projects', [ProjectPageController::class, 'index'])->name('projects.index');
Route::get('/projects/{slug}', [ProjectPageController::class, 'show'])->where('slug', $slug)->name('projects.show');
Route::get('/about', AboutController::class)->name('about');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:public-forms')->name('contact.store');
Route::get('/consultation', [ConsultationController::class, 'show'])->name('consultation');
Route::post('/consultation', [ConsultationController::class, 'store'])->middleware('throttle:public-forms')->name('consultation.store');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
