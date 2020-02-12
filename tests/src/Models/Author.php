<?php namespace Gecche\Breeze\Tests\Models;

use Gecche\Breeze\Breeze;

/**
 * Class Code
 *
 * @package Gecche\AclTest\Tests\Models
 *
 */
class Author extends Breeze
{
    use Relations\AuthorRelations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'authors';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    public $ownerships = true;

    public $guarded = ['id'];


    public static $rules = [
       'surname' => 'required',
       'code' => 'required|unique:authors,code'
    ];

    public static $rulesSets = [
        'insert' => [
            'surname' => 'required',
            'code' => 'required|unique:authors,code'
        ],
        'edit' => [
            'surname' => 'required',
            'code' => 'required|unique:authors,code',
            'birthdate' => 'required',
        ],
    ];

    public static $customMessagesSets = [
        'insert' => [
            'surname.required' => 'you must insert an author with a surname',
        ],
        'edit' => [
            'surname.required' => 'ok, at least now you have to set a surname!',
        ],
    ];

    public static $relationsData = [
        'books' => [self::HAS_MANY, 'related' => Book::class],
        'coauthored' => [self::BELONGS_TO_MANY, 'related' => Book::class, 'table' => 'books_coauthors',
            'foreignPivotKey' => 'coauthor_id',
        ],
    ];

}
