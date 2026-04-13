<?php

test('registration routes are disabled for this internal system', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});
