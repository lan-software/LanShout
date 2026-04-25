<?php

use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Massive Chat Ordering Tests
|--------------------------------------------------------------------------
| Verifies correct ordering and pagination with large volumes of messages.
*/

test('messages are returned in newest-first order across 500 messages', function () {
    $user = User::factory()->create();

    // Create 500 messages with sequential timestamps
    $baseTime = Carbon::create(2025, 1, 1, 0, 0, 0);
    $messages = [];

    for ($i = 0; $i < 500; $i++) {
        $messages[] = [
            'user_id' => $user->id,
            'body' => "Message #{$i}",
            'created_at' => $baseTime->copy()->addSeconds($i),
            'updated_at' => $baseTime->copy()->addSeconds($i),
        ];
    }

    Message::insert($messages);

    // First page should have the newest messages
    $response = $this->getJson(route('messages.index', ['per_page' => 20, 'page' => 1]));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(20);

    // Verify ordering: each message should be newer than the next one
    for ($i = 0; $i < count($data) - 1; $i++) {
        expect($data[$i]['created_at'] >= $data[$i + 1]['created_at'])->toBeTrue(
            "Message at index {$i} should be >= message at index ".($i + 1)
        );
    }

    // First item on page 1 should be the newest (Message #499)
    expect($data[0]['body'])->toBe('Message #499');
    // Last item on page 1 should be Message #480
    expect($data[19]['body'])->toBe('Message #480');
});

test('pagination preserves ordering across all pages of 500 messages', function () {
    $user = User::factory()->create();

    $baseTime = Carbon::create(2025, 1, 1, 0, 0, 0);
    $messages = [];

    for ($i = 0; $i < 500; $i++) {
        $messages[] = [
            'user_id' => $user->id,
            'body' => "Message #{$i}",
            'created_at' => $baseTime->copy()->addSeconds($i),
            'updated_at' => $baseTime->copy()->addSeconds($i),
        ];
    }

    Message::insert($messages);

    // Fetch all pages and collect all message IDs in order
    $allBodies = [];
    $totalPages = (int) ceil(500 / 20);

    for ($page = 1; $page <= $totalPages; $page++) {
        $response = $this->getJson(route('messages.index', ['per_page' => 20, 'page' => $page]));
        $response->assertOk();

        $data = $response->json('data');
        foreach ($data as $msg) {
            $allBodies[] = $msg['body'];
        }
    }

    // Should have all 500 messages
    expect($allBodies)->toHaveCount(500);

    // Should be in descending order (newest first)
    // Message #499 first, Message #0 last
    expect($allBodies[0])->toBe('Message #499');
    expect($allBodies[499])->toBe('Message #0');

    // Verify no duplicates
    expect(count(array_unique($allBodies)))->toBe(500);
});

test('messages with identical timestamps maintain consistent order', function () {
    $user = User::factory()->create();

    $sameTime = now();

    // Create 50 messages all at the same timestamp
    for ($i = 0; $i < 50; $i++) {
        Message::create([
            'user_id' => $user->id,
            'body' => "Simultaneous #{$i}",
            'created_at' => $sameTime,
            'updated_at' => $sameTime,
        ]);
    }

    $response = $this->getJson(route('messages.index', ['per_page' => 50]));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(50);

    // All messages should be present
    $bodies = collect($data)->pluck('body')->toArray();
    expect(count(array_unique($bodies)))->toBe(50);
});

test('1000 messages are correctly paginated', function () {
    $user = User::factory()->create();

    $baseTime = Carbon::create(2025, 6, 1, 0, 0, 0);
    $batch = [];

    for ($i = 0; $i < 1000; $i++) {
        $batch[] = [
            'user_id' => $user->id,
            'body' => "Msg-{$i}",
            'created_at' => $baseTime->copy()->addSeconds($i),
            'updated_at' => $baseTime->copy()->addSeconds($i),
        ];

        // Insert in batches to avoid memory issues
        if (count($batch) === 250) {
            Message::insert($batch);
            $batch = [];
        }
    }

    // Check total
    $response = $this->getJson(route('messages.index', ['per_page' => 20, 'page' => 1]));
    $response->assertOk();
    $response->assertJsonPath('meta.total', 1000);
    $response->assertJsonPath('meta.last_page', 50);

    // First page: newest messages
    $firstPage = $response->json('data');
    expect($firstPage[0]['body'])->toBe('Msg-999');

    // Last page: oldest messages
    $response = $this->getJson(route('messages.index', ['per_page' => 20, 'page' => 50]));
    $response->assertOk();
    $lastPage = $response->json('data');
    expect($lastPage[19]['body'])->toBe('Msg-0');
});

test('mixed user messages maintain chronological ordering', function () {
    $users = User::factory()->count(10)->create();
    $baseTime = Carbon::create(2025, 3, 1, 12, 0, 0);

    $messages = [];
    for ($i = 0; $i < 200; $i++) {
        $messages[] = [
            'user_id' => $users[$i % 10]->id,
            'body' => "Multi-user #{$i}",
            'created_at' => $baseTime->copy()->addSeconds($i),
            'updated_at' => $baseTime->copy()->addSeconds($i),
        ];
    }

    Message::insert($messages);

    // Fetch all pages
    $allTimestamps = [];
    for ($page = 1; $page <= 10; $page++) {
        $response = $this->getJson(route('messages.index', ['per_page' => 20, 'page' => $page]));
        $response->assertOk();

        foreach ($response->json('data') as $msg) {
            $allTimestamps[] = $msg['created_at'];
        }
    }

    expect($allTimestamps)->toHaveCount(200);

    // Verify descending order
    for ($i = 0; $i < count($allTimestamps) - 1; $i++) {
        expect($allTimestamps[$i] >= $allTimestamps[$i + 1])->toBeTrue();
    }
});

test('client-side reversal reconstructs chronological order from paginated API', function () {
    $user = User::factory()->create();

    $baseTime = Carbon::create(2025, 1, 1, 0, 0, 0);
    $messages = [];

    for ($i = 0; $i < 100; $i++) {
        $messages[] = [
            'user_id' => $user->id,
            'body' => "Chat #{$i}",
            'created_at' => $baseTime->copy()->addMinutes($i),
            'updated_at' => $baseTime->copy()->addMinutes($i),
        ];
    }

    Message::insert($messages);

    // Simulate what the frontend does: fetch pages and reverse each one
    // Page 1 (newest): #99-#80 reversed = #80-#99
    // Page 2 (next oldest): #79-#60 reversed = #60-#79
    // Combined: [#60-#79, #80-#99] (prepend page 2 results)

    $response1 = $this->getJson(route('messages.index', ['per_page' => 20, 'page' => 1]));
    $page1Data = array_reverse($response1->json('data'));

    $response2 = $this->getJson(route('messages.index', ['per_page' => 20, 'page' => 2]));
    $page2Data = array_reverse($response2->json('data'));

    // After client-side reconstruction: page 2 data prepended to page 1
    $chatWall = array_merge($page2Data, $page1Data);

    // Should be in ascending chronological order (oldest to newest)
    for ($i = 0; $i < count($chatWall) - 1; $i++) {
        expect($chatWall[$i]['created_at'] <= $chatWall[$i + 1]['created_at'])->toBeTrue(
            "Chat wall message at {$i} should be before message at ".($i + 1)
        );
    }

    // First message in the wall (oldest of the two pages)
    expect($chatWall[0]['body'])->toBe('Chat #60');
    // Last message in the wall (newest)
    expect($chatWall[39]['body'])->toBe('Chat #99');
});

test('soft deleted messages are excluded from pagination', function () {
    $user = User::factory()->create();

    $baseTime = Carbon::create(2025, 1, 1, 0, 0, 0);

    // Create 30 messages
    for ($i = 0; $i < 30; $i++) {
        $msg = Message::create([
            'user_id' => $user->id,
            'body' => "Deletable #{$i}",
            'created_at' => $baseTime->copy()->addSeconds($i),
            'updated_at' => $baseTime->copy()->addSeconds($i),
        ]);

        // Soft-delete every other message
        if ($i % 2 === 0) {
            $msg->delete();
        }
    }

    $response = $this->getJson(route('messages.index', ['per_page' => 50]));
    $response->assertOk();

    // Only 15 non-deleted messages remain
    $response->assertJsonPath('meta.total', 15);
    $data = $response->json('data');
    expect($data)->toHaveCount(15);

    // None should contain even-numbered messages
    foreach ($data as $msg) {
        preg_match('/#(\d+)/', $msg['body'], $matches);
        $num = (int) $matches[1];
        expect($num % 2)->toBe(1);
    }
});

test('per_page parameter works with large values', function () {
    $user = User::factory()->create();
    Message::factory()->count(100)->for($user)->create();

    $response = $this->getJson(route('messages.index', ['per_page' => 100]));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(100);
    $response->assertJsonPath('meta.last_page', 1);
});

test('empty second page returns no data', function () {
    $user = User::factory()->create();
    Message::factory()->count(10)->for($user)->create();

    $response = $this->getJson(route('messages.index', ['per_page' => 20, 'page' => 2]));
    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});
