<?php

namespace Tests\Feature\tests\Feature\AtomicIA;

use Tests\TestCase;

class DeleteConversationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
