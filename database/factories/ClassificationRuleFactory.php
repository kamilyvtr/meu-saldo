<?php

namespace Database\Factories;

use App\Models\ClassificationRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassificationRule>
 */
class ClassificationRuleFactory extends Factory
{
    protected $model = ClassificationRule::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'field' => 'description',
            'operator' => 'contains',
            'pattern' => 'Uber',
            'category_id' => null,
            'priority' => 0,
        ];
    }
}
