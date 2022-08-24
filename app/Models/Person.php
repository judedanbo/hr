<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [];

    /// get full name of person
    public function getFullNameAttribute()
    {
        return "{$this->title} {$this->other_names} {$this->surname}" ;
    }

    public function scopeOrderDob($query){
        return $query->orderBy('date_of_birth');
    }

    function getInitialsAttribute(){
        return strtoupper(substr($this->other_names, 0, 1) . substr($this->surname, 0, 1));
    }

    public function getNumberAttribute()
    {
        return Person::count();
    }

}