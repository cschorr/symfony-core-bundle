<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Security;

use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Unit test for UserChecker.
 * Tests that locked and inactive users are prevented from authenticating.
 */
class UserCheckerTest extends TestCase
{
    private UserChecker $userChecker;

    protected function setUp(): void
    {
        $this->userChecker = new UserChecker();
    }

    public function testCheckPreAuthWithActiveAndUnlockedUser(): void
    {
        $user = new User();
        $user->setActive(true);
        $user->setLocked(false);

        // Should not throw an exception
        $this->userChecker->checkPreAuth($user);

        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isLocked());
    }

    public function testCheckPreAuthWithLockedUser(): void
    {
        $user = new User();
        $user->setActive(true);
        $user->setLocked(true);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Your account has been locked. Please contact an administrator.');

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthWithInactiveUser(): void
    {
        $user = new User();
        $user->setActive(false);
        $user->setLocked(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Your account is inactive. Please contact an administrator.');

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthWithLockedAndInactiveUser(): void
    {
        $user = new User();
        $user->setActive(false);
        $user->setLocked(true);

        // Locked check happens first, so we should get the locked message
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Your account has been locked. Please contact an administrator.');

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthWithNonUserInterfaceObject(): void
    {
        $mockUser = $this->createMock(UserInterface::class);

        // Should not throw an exception for non-User instances
        $this->userChecker->checkPreAuth($mockUser);

        // If we reach here, the test passed (no exception was thrown)
        $this->assertTrue(true);
    }

    public function testCheckPostAuthDoesNothing(): void
    {
        $user = new User();
        $user->setActive(true);
        $user->setLocked(false);

        // checkPostAuth should be a no-op and not throw any exceptions
        $this->userChecker->checkPostAuth($user);

        // Test with locked user - should still not throw since checkPostAuth doesn't validate
        $user->setLocked(true);
        $this->userChecker->checkPostAuth($user);

        // Test with inactive user - should still not throw since checkPostAuth doesn't validate
        $user->setActive(false);
        $this->userChecker->checkPostAuth($user);

        // If we reach here, the test passed (no exceptions were thrown)
        $this->assertTrue(true);
    }

    public function testExceptionMessagesAreUserFriendly(): void
    {
        // Test locked message
        $lockedUser = new User();
        $lockedUser->setLocked(true);

        try {
            $this->userChecker->checkPreAuth($lockedUser);
            $this->fail('Expected CustomUserMessageAccountStatusException to be thrown');
        } catch (CustomUserMessageAccountStatusException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('locked', $message);
            $this->assertStringContainsString('administrator', $message);
            $this->assertStringNotContainsString('technical', strtolower($message));
            $this->assertStringNotContainsString('error', strtolower($message));
        }

        // Test inactive message
        $inactiveUser = new User();
        $inactiveUser->setActive(false);

        try {
            $this->userChecker->checkPreAuth($inactiveUser);
            $this->fail('Expected CustomUserMessageAccountStatusException to be thrown');
        } catch (CustomUserMessageAccountStatusException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('inactive', $message);
            $this->assertStringContainsString('administrator', $message);
            $this->assertStringNotContainsString('technical', strtolower($message));
            $this->assertStringNotContainsString('error', strtolower($message));
        }
    }

    public function testMultipleChecksOnSameUser(): void
    {
        $user = new User();
        $user->setActive(true);
        $user->setLocked(false);

        // First check should pass
        $this->userChecker->checkPreAuth($user);

        // Second check should also pass
        $this->userChecker->checkPreAuth($user);

        // Lock the user
        $user->setLocked(true);

        // Now it should throw
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthPreservesUserState(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setActive(true);
        $user->setLocked(false);
        $user->setFirstName('John');
        $user->setLastName('Doe');

        $this->userChecker->checkPreAuth($user);

        // Verify user state is unchanged
        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isLocked());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
    }
}
