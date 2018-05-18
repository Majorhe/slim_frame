<?php
/**
 * Created by PhpStorm.
 * User: shasnhanpc
 * Date: 2018/5/17
 * Time: 16:28
 */

namespace models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'admin';

    protected $fillable = ['id', 'name', 'password', 'email', 'phone', 'salt', 'status', 'created_time'];

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
}