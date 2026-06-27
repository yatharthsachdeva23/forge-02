<?php

test('health endpoint returns ok status', function () {
    $response = $this->getJson('/api/health');

    $response->assertStatus(200);
    $response->assertJson(['status' => 'ok']);
});
