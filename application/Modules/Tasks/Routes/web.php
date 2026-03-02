<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Http\Controllers\TasksController;

Route::prefix('tasks')->middleware(['web', 'auth'])->group(function() {
    Route::get('/', [TasksController::class, 'index'])->name('tasks.dashboard');

    Route::get('all-tasks', [TasksController::class, 'tasks'])->name('tasks.all');
    Route::get('add-task', [TasksController::class, 'addTask'])->name('tasks.addTasks');
    Route::post('create-task', [TasksController::class, 'registerTask'])->name('tasks.registerTask');
    Route::post('update-task/{id}', [TasksController::class, 'updateTask'])->name('tasks.updateTask');
    Route::get('view-task/{id}', [TasksController::class, 'viewTask'])->name('tasks.viewTask');
    Route::get('delete-task/{id}', [TasksController::class, 'deleteTask'])->name('tasks.deleteTask');
    Route::get('view-file/{id}', [TasksController::class, 'viewFile'])->name('tasks.viewFile');
    Route::get('delete-file/{id}', [TasksController::class, 'deleteFile'])->name('tasks.deleteFile');

    Route::post('add-subtask/{id}', [TasksController::class, 'addSubtask'])->name('tasks.addSubtask');
    Route::post('update-subtask/{id}', [TasksController::class, 'updateSubtask'])->name('tasks.updateSubtask');
    Route::get('delete-subtask/{id}', [TasksController::class, 'deleteSubtask'])->name('tasks.deleteSubtask');


    Route::get('manage-users', [TasksController::class, 'manageUsers'])->name('tasks.manageUsers');
    Route::post('update-user-role', [TasksController::class, 'updateUserRole'])->name('tasks.updateUserRole');
    Route::get('delete-user-role/{id}', [TasksController::class, 'deleteUserRole'])->name('tasks.deleteUserRole');

    Route::post('task-messages', [TasksController::class, 'storeComment'])->name('tasks.storeComment');
    Route::get('tasks/{taskId}/comments', [TasksController::class, 'getComments'])->name('tasks.getComments');

    Route::get('notifications/list', [TasksController::class, 'list'])->name('tasks.notifications');
    Route::get('notifications/{id}', [TasksController::class, 'details'])->name('tasks.viewNotification');

});
