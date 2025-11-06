# Design Document

## Overview

This design improves the user experience when an unapproved user attempts to login by replacing the current error-based approach with a user-friendly informational notification system. The solution maintains all existing security measures while providing a more welcoming and clear communication to users about their account status.

## Architecture

The solution follows Laravel's existing authentication event system and integrates with the current UI components. The architecture consists of:

1. **Enhanced CheckUserApproved Listener**: Modified to use session flash messages instead of validation errors
2. **New Notification Component**: A dedicated Blade component for displaying approval status messages
3. **Updated Login View**: Integration of the new notification component with appropriate styling

## Components and Interfaces

### 1. CheckUserApproved Listener Enhancement

**Location**: `app/Listeners/CheckUserApproved.php`

**Changes**:
- Replace `withErrors()` method with session flash message
- Use `with('approval_pending', $message)` to set informational message
- Maintain existing security measures (logout, session invalidation, token regeneration)

**Interface**:
```php
public function handle(Authenticated $event): void
{
    if ($event->user && !$event->user->is_approved) {
        // Security measures (unchanged)
        $this->guard->logout();
        $this->request->session()->invalidate();
        $this->request->session()->regenerateToken();
        
        // New: Use flash message instead of error
        return redirect('/login')->with('approval_pending', $message);
    }
}
```

### 2. Approval Notification Component

**Location**: `resources/views/components/approval-notification.blade.php`

**Purpose**: Display user-friendly approval pending messages with appropriate styling

**Design Features**:
- Uses informational styling (blue/orange theme) instead of error styling (red theme)
- Consistent with existing modal design patterns from validation-errors component
- Includes reassuring messaging and clear next steps
- Maintains accessibility features (focus management, keyboard navigation)

**Interface**:
```php
@if (session('approval_pending'))
    <!-- Approval Notification Modal -->
    <div id="approval-notification-modal" class="...">
        <!-- Modal content with informational styling -->
    </div>
@endif
```

### 3. Login View Integration

**Location**: `resources/views/auth/login.blade.php`

**Changes**:
- Add approval notification component after existing validation errors
- Ensure proper component ordering for user experience
- Maintain existing functionality and styling

## Data Models

No changes to existing data models are required. The solution uses:
- Existing `User` model with `is_approved` field
- Laravel's session system for flash messages
- Existing authentication events system

## Error Handling

### Security Measures (Maintained)
- User logout on approval check failure
- Session invalidation and token regeneration
- Proper redirect handling

### User Experience Improvements
- Clear messaging about account status
- Informational styling instead of error styling
- Guidance on next steps (contact support, wait for approval)
- Consistent modal behavior with existing UI patterns

### Fallback Handling
- If session flash message fails, fallback to basic redirect
- Graceful degradation for JavaScript-disabled browsers
- Proper error logging for debugging

## Testing Strategy

### Unit Tests
- Test CheckUserApproved listener behavior with approved/unapproved users
- Verify session flash message creation
- Confirm security measures are maintained

### Integration Tests
- Test complete login flow for unapproved users
- Verify notification display and styling
- Test modal interaction and dismissal

### User Experience Tests
- Verify notification appears correctly
- Test modal accessibility features
- Confirm message clarity and helpfulness

## Implementation Details

### Message Content
The approval pending message will be:
- Positive and reassuring in tone
- Clear about the current status
- Provide actionable next steps
- Include contact information if available

### Styling Approach
- Reuse existing modal structure from validation-errors component
- Replace red error styling with blue/orange informational styling
- Maintain consistent spacing, typography, and interaction patterns
- Ensure responsive design compatibility

### JavaScript Behavior
- Modal auto-display on page load when message exists
- Dismissal functionality (close button, outside click, escape key)
- Focus management for accessibility
- Animation consistency with existing modals

## Security Considerations

- All existing security measures are preserved
- No sensitive information exposed in client-side messages
- Proper session handling and CSRF protection maintained
- User logout and session invalidation remain intact

## Accessibility Features

- Proper ARIA labels and roles
- Keyboard navigation support
- Focus management (auto-focus on dismiss button)
- Screen reader compatible messaging
- High contrast color schemes maintained