<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\EmbedCommit;
use App\Jobs\LinkCommitTopics;
use App\Models\Commit;
use App\Queries\MentionedUsersQuery;
use App\Services\EmojiShortcodes;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCommitBody
{
    public function __construct(
        private MentionedUsersQuery $mentionedUsers,
        private EmojiShortcodes $emojiShortcodes,
        private CreateMentionNotifications $createMentionNotifications,
    ) {
        //
    }

    public function handle(Commit $commit, string $body): Commit
    {
        return DB::transaction(function () use ($commit, $body): Commit {
            $commit = Commit::query()->lockForUpdate()->findOrFail($commit->id);
            $body = $this->emojiShortcodes->replace($body);

            $commit->forceFill([
                'body' => $body,
                'mentions' => $this->mentionedUsers->get($body),
                'embedding' => null,
            ])->save();

            $this->createMentionNotifications->handle($commit);
            LinkCommitTopics::dispatchFor($commit);

            if ($body !== '') {
                dispatch(new EmbedCommit($commit))->afterCommit();
            }

            return $commit->refresh();
        }, attempts: 3);
    }
}
