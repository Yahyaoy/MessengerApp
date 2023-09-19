<?php

namespace App\Http\Controllers;

use App\Events\MessageCreated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Recipient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MessagesControllers extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $user = Auth::user();
        $conversation = $user->conversations()->findOrFail($id);
        return $conversation->messages()->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // First, we validate the incoming request to make sure it follows the rules.
        request()->validate([
            'body' => 'required|string|max:255', // The message should not be empty and not too long.
            'conversation_id' => [
                'int', // It should be an integer (a whole number).
                'exists:conversations,id', // And it should exist in our conversations.
                Rule::requiredIf(function () use ($request) {
                    return !$request->input('user_id'); // We require a conversation_id if user_id doesn't exist.
                })
            ],

            'user_id' => [
                'int', // User_id should also be a whole number.
                'exists:users,id', // And it should exist in our list of users.
                Rule::requiredIf(function () use ($request) {
                    return !$request->input('conversation_id'); // We require a user_id if conversation_id doesn't exist.
                })
            ],
        ]);

        // Next, we find the user with ID 1. This code assumes there's a user with ID 1.
        $user = User::find(1);

        // We get the conversation_id and user_id from the request.
        $conversation_id = $request->input('conversation_id');
        $user_id = $request->input('user_id');

        // We start a database transaction. Think of it as a way to group database actions together.
        DB::beginTransaction();

        try {
            // If conversation_id exists, we find the conversation.
            if ($conversation_id) {
                $conversation = $user->conversations()->findOrFail($conversation_id);
            } else {
                // If conversation_id doesn't exist, we create a new "peer" conversation.
                $conversation = Conversation::where('type', '=', 'peer')
                    ->whereHas('participants', function ($builder) use ($user_id, $user) {
                        // This part checks if there's a conversation between the user and another person.
                        $builder->join('participants as participants2', 'participants2.conversation_id', '=', 'participants.conversation_id')
                            ->where('participants.user_id', '=', $user_id)
                            ->where('participants2.user_id', '=', $user->id);
                    })->first();

                if (!$conversation) {
                    // If there's no conversation, we create a new one and attach both users to it.
                    $conversation = Conversation::create([
                        'user_id' => $user->id,
                        'type' => 'peer'
                    ]);

                    $conversation->participants()->attach([
                        $user->id => ['joined_at' => now()],
                        $user_id => ['joined_at' => now()],
                    ]);
                }
            }

            // We create a new message in the conversation.
            $message = $conversation->messages()->create([
                'user_id' => $user->id,
                'body' => $request->input('body')
            ]);

            // Now, we add recipients to the message. This part can be a bit complex.
            DB::statement('
                INSERT INTO recipients (user_id, message_id)
                SELECT user_id, ? FROM participants
                WHERE conversation_id = ?
    ', [$message->id, $conversation->id]);

            // We update the conversation's last message ID to the ID of the new message.
            $conversation->update([
                'last_message_id' => $message->id
            ]);

            // We commit the transaction. If everything worked, changes are saved in the database.
            DB::commit();

            // Finally, we broadcast (send) the new message to everyone involved.
            broadcast(new MessageCreated($message));

        } catch (\Throwable $e) {
            // If something went wrong, we roll back the transaction to keep the database safe.
            DB::rollBack();
            throw $e;
        }

        // We return the message to confirm that it was sent successfully.
        return $message;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        Recipient::where([
            'user_id' => Auth::id(),
            'message_id' => $id
        ])->delete();

        return [
            'message' => 'deleted'
        ];
    }
}
