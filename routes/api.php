<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseDetails;
use App\Http\Controllers\EditCourse;
use App\Http\Controllers\LatestCourses;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RelatedCourses;
use App\Http\Controllers\SearchCourse;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TrendingCourse;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\VerifyCode;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/verifycode', [VerifyCode::class, 'verifycode']);
Route::post('/resendcode', [VerifyCode::class, 'resendcode']);
Route::post('/checkemail', [RegisterController::class, 'checkEmail']);
Route::post('/passwordverifycode', [RegisterController::class, 'passwordVerifyCode']);
Route::post('/updateprofile', [RegisterController::class, 'updateProfile']);
Route::post('/updatebank', [RegisterController::class, 'updateBank']);
Route::post('/postcourse', [TutorController::class, 'postCourse']);
Route::get('/getsections', [TutorController::class, 'getSections']);
Route::get('/createdcourse', [TutorController::class, 'createdCourse']);
Route::post('/uploadvideos', [TutorController::class, 'uploadVideos']);
Route::post('/uploadfiles', [TutorController::class, 'uploadFiles']);
Route::get('/withdrawhistory', [TutorController::class, 'withdrawHistory']);
Route::get('/latestcourses', [LatestCourses::class, 'latestCourses']);
Route::get('/trendingcourses', [TrendingCourse::class, 'trendingCourses']);
Route::post('/publishcourse', [TutorController::class, 'publishcourse']);
Route::post('/savevideos', [TutorController::class, 'saveVideos']);
Route::get('/searchcourse', [SearchCourse::class, 'searchCourse']);
Route::get('/searchcategory', [SearchCourse::class, 'searchCategory']);
Route::get('/suggestions', [SearchCourse::class, 'suggestions']);
Route::get('/coursedetails', [CourseDetails::class, 'courseDetails']);
Route::get('/courseaccess', [CourseDetails::class, 'courseAccess']);
Route::get('/coursepreview', [CourseDetails::class, 'coursePreview']);
Route::get('/coursefiles', [CourseDetails::class, 'courseFiles']);
Route::post('/progress', [CourseDetails::class, 'progress']);
Route::get('/relatedcourses', [RelatedCourses::class, 'relatedCourses']);
Route::post('/preparepayment', [PaymentController::class, 'preparePayment']);
Route::get('/payment', [PaymentController::class, 'payment']);
Route::post('/withdraw', [PaymentController::class, 'withdraw']);
Route::get('/verifypayment', [PaymentController::class, 'verifyPayment']);
Route::get('/purchasedcourse', [StudentController::class, 'purhcasedCourse']);
Route::get('/wishlist', [StudentController::class, 'wishlist']);
Route::post('/addwishlist', [StudentController::class, 'addwishlist']);
Route::get('/transactions', [StudentController::class, 'transactions']);
Route::post('/savenote', [StudentController::class, 'saveNote']);
Route::get('/getnote', [StudentController::class, 'getNote']);
Route::post('/clearnote', [StudentController::class, 'clearNote']);
Route::post('/sendreview', [StudentController::class, 'sendReview']);
Route::get('/receipt', [PaymentController::class, 'receipt']);
Route::post('/editbasicdetails', [EditCourse::class, 'editBasicDetails']);
Route::get('/coursereview', [CourseDetails::class, 'courseReview']);
Route::get('/sectiondetails', [EditCourse::class, 'sectionDetails']);
Route::post('/deletevideo', [EditCourse::class, 'deleteVideo']);
Route::post('/addsection', [EditCourse::class, 'addSection']);
Route::post('/editsectionname', [EditCourse::class, 'editSectionName']);
Route::post('/deletesection', [EditCourse::class, 'deleteSection']);
Route::post('/deletefile', [EditCourse::class, 'deleteFile']);
Route::post('/addnewvideo', [EditCourse::class, 'addNewVideo']);
Route::post('/addnewfile', [EditCourse::class, 'addNewFile']);
Route::get('/tutorpreview', [TutorController::class, 'tutorPreview']);
Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {
    Route::post('login',  [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', [AuthController::class, 'me']);
});
