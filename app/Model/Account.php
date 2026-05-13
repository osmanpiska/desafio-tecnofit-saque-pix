<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class Account extends Model
{
    public bool $incrementing = false;

    protected ?string $table = 'account';

    protected string $primaryKey = 'id';

    protected string $keyType = 'string';

    protected array $fillable = ['id', 'name', 'balance', 'created_at', 'updated_at'];

    protected array $casts = [
        'balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function withdraws()
    {
        return $this->hasMany(AccountWithdraw::class, 'account_id', 'id');
    }
}
