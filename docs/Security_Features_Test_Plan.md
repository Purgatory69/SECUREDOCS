# SECUREDOCS — Advanced Security Features Test Plan

## DLP (Data Loss Prevention) Testing

### Backend Tests
- [ ] Upload file with SSN patterns (xxx-xx-xxxx)
- [ ] Upload file with credit card numbers
- [ ] Upload file with email addresses
- [ ] Verify `dlp_scan_results` table population
- [ ] Test DLP action enforcement (warn/block/quarantine)
- [ ] Test pattern matching accuracy
- [ ] Test keyword detection sensitivity

### API Endpoints
- [ ] `POST /files/{id}/dlp-scan` - Manual DLP scan
- [ ] `GET /dlp-scan-results?policy_id=&risk_score=` - Results query
- [ ] `PUT /dlp-scan-results/{id}/approve` - False positive handling

## Security Policy Testing

### Access Control Tests
- [ ] IP whitelist allows only specified IPs
- [ ] IP blacklist blocks specified IPs  
- [ ] Country restrictions work correctly
- [ ] Time-based access enforced with timezone
- [ ] 2FA requirement per policy
- [ ] Device approval requirement enforcement

### File Permission Tests
- [ ] Download restrictions (`allow_download`)
- [ ] Copy restrictions (`allow_copy`)
- [ ] Print restrictions (`allow_print`)
- [ ] Screenshot restrictions (`allow_screenshot`)
- [ ] Watermark overlay (`watermark_enabled`)

### API Endpoints
- [ ] `GET /security-policies` - List policies by scope
- [ ] `POST /security-policies` - Create new policy
- [ ] `PUT /security-policies/{id}/enforce` - Activate policy
- [ ] `DELETE /security-policies/{id}` - Disable policy

## Trusted Device Management

### Device Registration Tests
- [ ] New device detection and fingerprinting
- [ ] Device name auto-generation
- [ ] Device type detection (desktop/mobile/tablet)
- [ ] Trust level assignment
- [ ] Device approval workflow

### Device Management Tests
- [ ] Device revocation
- [ ] Trust level modification
- [ ] Device expiration handling
- [ ] Location tracking updates
- [ ] Access restriction enforcement

### API Endpoints
- [ ] `GET /trusted-devices` - List user's devices
- [ ] `POST /trusted-devices/{id}/trust` - Approve device
- [ ] `POST /trusted-devices/{id}/revoke` - Revoke device
- [ ] `PUT /trusted-devices/{id}/extend` - Extend expiration

## Security Violation Testing

### Violation Generation Tests
- [ ] Access denied violations logged
- [ ] DLP trigger violations recorded
- [ ] Suspicious activity detection
- [ ] Policy violation logging
- [ ] Automatic severity assignment

### Violation Management Tests
- [ ] Violation investigation workflow
- [ ] False positive marking
- [ ] Violation resolution
- [ ] Escalation procedures
- [ ] Automated responses

### API Endpoints
- [ ] `GET /security-violations?status=&severity=` - List violations
- [ ] `PUT /security-violations/{id}/resolve` - Resolve violation
- [ ] `PUT /security-violations/{id}/investigate` - Start investigation
- [ ] `POST /security-violations/{id}/false-positive` - Mark as false positive

## Integration Tests
- [ ] DLP + File upload workflow
- [ ] Security policy + File sharing
- [ ] Trusted device + Login process
- [ ] Violation + Alert system
