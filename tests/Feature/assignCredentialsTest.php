<?php

use App\Models\Credential;

test('a credential can be assigned to a user, Employee, or Organization', function () {
    $user_id = 1;
    $cred = Credential::factory()->create();
    $cred->assignCredential($user_id);
    $this->assertCount(1,$cred->assigned);
});
