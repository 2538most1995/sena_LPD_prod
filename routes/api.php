<?php

use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentSettingController;
use App\Http\Controllers\Api\LecturerController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectActivityController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReferenceDataController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentImportController;
use App\Http\Controllers\Api\SystemStatusController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/system/status', SystemStatusController::class)
        ->middleware('throttle:60,1');

    Route::get('/me', function () {
        $user = request()->user();

        return response()->json(['data' => $user?->only([
            'id', 'school_id', 'display_name', 'school_name', 'teacher_name', 'position',
            'address_line', 'subdistrict', 'district', 'province', 'postal_code', 'phone',
            'latitude', 'longitude', 'photo_path', 'parent_id', 'role', 'status', 'updated_at',
        ])]);
    })->middleware('web');

    Route::middleware(['web', 'auth'])->group(function (): void {
        Route::get('/dashboard', DashboardController::class);
        Route::get('/references', ReferenceDataController::class);
        Route::get('/reports/open', [ReportController::class, 'open'])->name('api.reports.open');
        Route::get('/approvals', ApprovalController::class);
        Route::get('/audit-logs', AuditLogController::class);

        Route::get('/courses', [CourseController::class, 'index']);
        Route::post('/courses', [CourseController::class, 'store']);
        Route::match(['post', 'put'], '/courses/{course}', [CourseController::class, 'update']);
        Route::delete('/courses/{course}', [CourseController::class, 'destroy']);
        Route::post('/courses/{course}/review', [CourseController::class, 'review']);
        Route::get('/courses/{course}/files/{type}', [CourseController::class, 'file'])->name('api.courses.file');

        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::get('/projects/{project}', [ProjectController::class, 'show']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
        Route::post('/projects/{project}/review', [ProjectController::class, 'review']);
        Route::put('/projects/{project}/participants', [ProjectActivityController::class, 'participants']);
        Route::put('/projects/{project}/scores', [ProjectActivityController::class, 'scores']);
        Route::post('/projects/{project}/photos', [ProjectActivityController::class, 'uploadPhotos']);
        Route::delete('/projects/{project}/photos/{photo}', [ProjectActivityController::class, 'deletePhoto']);
        Route::get('/projects/{project}/photos/{photo}/file', [ProjectActivityController::class, 'photoFile'])->name('api.projects.photos.file');

        Route::get('/students/import-template', [StudentImportController::class, 'template']);
        Route::post('/students/import', [StudentImportController::class, 'import']);
        Route::apiResource('students', StudentController::class)->except(['show']);
        Route::apiResource('lecturers', LecturerController::class)->except(['show']);
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::post('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        Route::post('/profile', [ProfileController::class, 'update']);
        Route::get('/profile/photo', [ProfileController::class, 'photo'])->name('api.profile.photo');
        Route::get('/document-settings', [DocumentSettingController::class, 'show']);
        Route::post('/document-settings', [DocumentSettingController::class, 'update']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);
    });
});
