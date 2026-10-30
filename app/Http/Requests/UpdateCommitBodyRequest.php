<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Commit;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCommitBodyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        assert($user instanceof User);

        $commit = $this->route('commit');
        assert($commit instanceof Commit);

        return $user->id === $commit->user_id;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    public function rules(): array
    {
        $commit = $this->route('commit');
        assert($commit instanceof Commit);

        return [
            'body' => CreateCommitRequest::bodyRules($commit->images()->exists()),
        ];
    }

    public function prepareForValidation(): void
    {
        CreateCommitRequest::normalizeBody($this);
    }
}
