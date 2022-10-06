<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'earnings',
        'phone',
        'email',
        'password',
        'bank_code',
        'acc_no',
        'pin',
        'bank_acc_name'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

   /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function code(){
        return  $this->hasMany(VerificationCode::class);
     }

     public function course(){
        return  $this->hasMany(Course::class);
     }

     public function courseNote(){
        return  $this->hasMany(CourseNote::class);
     }

     public function review(){
        return  $this->hasMany(CourseReview::class);
     }

     public function progress(){
        return  $this->hasMany(Progress::class);
     }

     
     public function payment(){
        return  $this->hasMany(Payment::class);
     }

     public function mycourse(){
        return  $this->hasMany(MyCourse::class);
     }

    
}
