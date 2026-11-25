# Code Audit Report

**Project:** `edydeyemi/laravel-paystack`  
**Review date:** 2026-11-25  
**Scope:** Package source, configuration, routes, tests, Composer metadata, and user-facing documentation.  
**Review type:** Static code and design review; no live Paystack calls or production configuration were available.

## Executive summary

The package has a clear resource-oriented structure and verifies webhook signatures using the raw request body and constant-time comparison. The high-impact client and amount-conversion findings have been addressed, and the documentation now shows safer webhook handling.

## Implementation follow-up — 2026-11-25

| Finding | Status |
|---|---|
| Automatic retries on writes | Fixed: configured retries now apply to GET requests only. |
| Amount conversion overflow | Fixed: exact decimal arithmetic is used, precision and input size are bounded, and overflow is rejected. Formatting also preserves large integer values. |
| Duplicate webhook processing | Application responsibility: examples now use atomic state transitions and explain at-least-once delivery. The stateless package still dispatches every verified delivery. |
| Synchronous webhook listeners | Clarified in code comments and documentation; business work should use an asynchronous queue. |
| Custom API host secret egress | Mitigated: HTTPS is required, custom hosts need explicit opt-in, and URL credentials/query/fragments are rejected. |
| CSRF path guidance | Updated to require the exception path to match the configured webhook path. |
| Full payload logging example | Removed; the example logs only event and reference. |

## Findings

### 1. High — Configured retries can repeat financial write requests

`PaystackClient::send()` originally applied Laravel's retry policy to every HTTP verb, including POST requests such as transfers and refunds. It now configures retries only for GET requests ([PaystackClient.php](../src/Http/PaystackClient.php#L126)). The retry count remains configurable ([paystack.php](../config/paystack.php#L41)), and the installation guide explains the GET-only behavior ([01-installation-and-configuration.md](01-installation-and-configuration.md#L39)).

If Paystack processes a write but the response is lost or a transient error is returned, retrying may submit the operation again. Whether a duplicate is prevented depends on the specific endpoint and its idempotency/reference semantics; the client does not distinguish safe reads from writes.

**Recommendation:** Do not apply automatic retries globally to non-idempotent operations. Retry reads by default; only retry writes when the endpoint has a documented idempotency mechanism and the same idempotency key/reference is reused. Add tests that assert retry behavior separately for reads and writes.

### 2. Medium — Amount conversion can silently overflow

`Amount::toMinor()` originally converted the input to a float, multiplied by a power of ten, then cast directly to an integer. The implementation now uses bounded exact decimal arithmetic ([Amount.php](../src/Support/Amount.php#L42)) and rejects unrepresentable amounts. A probe of the former implementation with `'999999999999999999999999.99'` returned `-2537764285350674432`.

**Recommendation:** Use exact decimal/string arithmetic (or a suitable decimal library), define the supported precision and integer range, and reject values that cannot be represented. Add boundary, overflow, and rounding-policy tests.

### 3. Medium — Webhook side effects are not deduplicated or concurrency-safe in the documented example

The controller dispatches the generic event for every verified delivery, and then dispatches the typed event when mapped ([WebhookController.php](../src/Http/Controllers/WebhookController.php#L70)). There is no package-level delivery deduplication, which means provider retries or duplicate deliveries reach application listeners again. The example now uses a conditional atomic order update ([07-webhooks-and-events.md](07-webhooks-and-events.md#L65)); consuming applications remain responsible for idempotent fulfillment.

**Recommendation:** Treat webhooks as at-least-once. Make fulfillment and ledger changes idempotent using a stable transaction/reference key with a database uniqueness constraint or an atomic state transition/transaction. Update the example to demonstrate concurrency-safe handling.

### 4. Medium — Synchronous listeners can delay the webhook acknowledgement

The controller dispatches events before returning its HTTP 200 response ([WebhookController.php](../src/Http/Controllers/WebhookController.php#L70)). Laravel listeners run synchronously unless they are queued, so a slow or failing synchronous listener can delay or prevent acknowledgement. The misleading “immediately” comment was corrected, and the guide now explains the synchronous dispatch/queue behavior.

**Recommendation:** Keep the event dispatch path limited to a durable, fast handoff and clearly document that listeners doing I/O or business work must be queued. Correct the “immediately” comment so it describes the actual execution order.

### 5. Medium — Configurable API base URL receives the secret bearer token

The API base URL comes from `PAYSTACK_BASE_URL` ([paystack.php](../config/paystack.php#L27)), and the client attaches the configured secret key as a bearer token to requests sent to that URL ([PaystackClient.php](../src/Http/PaystackClient.php#L160)). This is useful for controlled testing/custom gateways, but a mistaken or compromised production setting can send the live Paystack key to another host.

**Recommendation:** Treat the base URL as highly sensitive trusted deployment configuration, never derive it from request/user input, and document the credential-egress implication. Consider validating HTTPS and the Paystack host in production while retaining an explicit opt-in for test endpoints.

### 6. Low — CSRF instructions do not follow a customized webhook path

The webhook route path is configurable ([paystack.php](../config/paystack.php#L67)), while both installation guides use a CSRF exception hard-coded to `paystack/webhook` ([01-installation-and-configuration.md](01-installation-and-configuration.md#L64), [USER_GUIDE.md](USER_GUIDE.md#L63)). The guides now call out that customized paths must match.

**Recommendation:** State that the CSRF exception must match the configured route path, or show how to configure both values from the same application setting.

### 7. Low — Catch-all logging example records the entire webhook payload

The webhook guide's catch-all listener originally logged `$event->payload` ([07-webhooks-and-events.md](07-webhooks-and-events.md#L93)). The example now logs only event and reference fields.

**Recommendation:** Log only required identifiers and event metadata by default. If full payload retention is necessary, apply access controls, retention limits, and field redaction.

## Positive observations

- Webhook verification uses the exact raw body and `hash_equals()` for signature comparison ([VerifyPaystackWebhookSignature.php](../src/Http/Middleware/VerifyPaystackWebhookSignature.php#L46), [WebhookSignature.php](../src/Webhooks/WebhookSignature.php#L52)).
- Secret-key absence fails explicitly for outbound API calls.
- Resource endpoint identifiers are generally URL-encoded where string values are accepted.
- The package uses Laravel's HTTP client and supports deterministic HTTP fakes in tests.
- Composer metadata validates, and static analysis currently reports no errors.

## Validation performed

- `vendor/bin/pest.bat` — **24 tests passed, 94 assertions**.
- `vendor/bin/phpstan.bat analyse --no-progress` — **passed at the configured level 8**.
- `vendor/bin/pint.bat --test` — **passed**.
- `composer validate --no-check-publish` — **valid**; Composer emitted only its root-version fallback warning because the checkout has no detected package version.
- Added `brick/math` as a direct runtime dependency for exact decimal arithmetic.

## Review limitations

This review did not exercise a live Paystack account, verify current provider retry policies, inspect a deployed Laravel application's proxy/CSRF configuration, or perform penetration testing. Findings concerning deployment configuration and listener behavior should be checked against the consuming application's actual setup.
