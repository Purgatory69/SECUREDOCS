# Implementation Plan

- [x] 1. Create approval notification component





  - Create new Blade component at `resources/views/components/approval-notification.blade.php`
  - Implement modal structure similar to validation-errors but with informational styling
  - Use blue/orange color scheme instead of red error colors
  - Include proper accessibility features and JavaScript for modal behavior
  - _Requirements: 2.1, 2.2, 2.4_

- [x] 2. Update CheckUserApproved listener





  - Modify `app/Listeners/CheckUserApproved.php` to use session flash messages
  - Replace `withErrors()` call with `with('approval_pending', $message)`
  - Update message content to be more user-friendly and informational
  - Maintain all existing security measures (logout, session invalidation, token regeneration)
  - _Requirements: 1.1, 1.2, 1.3, 3.1, 3.2, 3.3, 3.4_

- [x] 3. Integrate notification component into login view





  - Add approval notification component to `resources/views/auth/login.blade.php`
  - Position component appropriately in the view (after validation errors)
  - Ensure proper component ordering and styling integration
  - Test that existing login functionality remains unchanged
  - _Requirements: 1.4, 2.3_

- [ ]* 4. Add unit tests for listener changes
  - Create tests for CheckUserApproved listener with approved and unapproved users
  - Test session flash message creation and content
  - Verify security measures are maintained in all scenarios
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [ ]* 5. Add integration tests for complete flow
  - Test complete login flow for unapproved users from browser perspective
  - Verify notification display and modal behavior
  - Test accessibility features and keyboard navigation
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4_