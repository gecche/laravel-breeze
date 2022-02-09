<?php namespace Gecche\Breeze\Tests\ModelsForCompiling;

use Gecche\Breeze\Breeze;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Code
 *
 * @package Gecche\AclTest\Tests\Models
 *
 */
class Book extends Breeze
{

	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'books';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public $guarded = ['id'];

    public static $relationsData = [
//        'books' => [self::HAS_ONE, 'related' => 'Address', 'foreignKey' => 'pippo', 'localKey' => 'ciccio'],
//        'user' => [self::HAS_ONE, 'related' => 'AddressTwo', 'localKey' => 'ciccio'],
//        'orders' => [self::HAS_MANY, 'related' => 'Order', 'foreignKey' => 'pippo', 'localKey' => 'ciccio'],
//        'attachments' => [self::BELONGS_TO_MANY, 'related' => 'App\Models\Attachment', 'table' => 'news_attachments'],
//        'userUpdatedBy' => [self::BELONGS_TO, 'related' => 'App\Models\User', 'foreignKey' => 'updated_by'],
//        'userCreatedBy' => [self::BELONGS_TO, 'related' => 'App\Models\User', 'foreignKey' => 'created_by'],
        'author' => [self::BELONGS_TO, 'related' => Author::class],
        'coauthors' => [self::BELONGS_TO_MANY, 'related' => Author::class, 'table' => 'books_coauthors',
                'relatedPivotKey' => 'coauthor_id',
        ],
    ];


}
