# What I did

## Understanding of the assignment

I treated trust as the main product requirement. A user must see where the price came from and how fresh it is, understand the exact buy/sell formula, get a server-owned locked quote, and know whether the trade completed. The system must refuse to trade if pricing cannot be trusted and must never leave cash, gold, inventory, or receipts partially updated.

## Product and architecture decisions

The repository is split into a Laravel JSON API and a React interface. Pricing providers implement a small domain contract; the application service applies ordered failover and a five-minute shared cache. This keeps third-party parsing out of trade logic and makes providers replaceable. PakGold is the preferred local source. GoldPrice.org is normalized from PKR per troy ounce into PKR per gram.

Quotes persist every input used to calculate the trade: market reference, final unit price, source, observation time, amount, total, and expiry. Confirmation never fetches or substitutes a new price. Settlement locks the quote and portfolio rows inside one database transaction. The trade table has a unique quote ID, so repeated or concurrent confirmation returns the same receipt rather than creating a second trade.

The React journey follows five visible ideas: see price and freshness, enter PKR or gold, review a 75-second lock, confirm once, and receive updated balances plus a receipt. The interface explains the markup and guardrail, remains usable on mobile, and turns provider failure into a clear trading-paused state.

## Assumptions

- One seeded portfolio represents the single demo customer and also holds platform inventory, as authentication and an admin panel are out of scope.
- Gold is 24K and stored in grams to six decimal places. PKR is stored to two decimal places.
- The buy guardrail is configurable and defaults to PKR 45,000 per gram because the brief names a guardrail without prescribing its value.
- Provider HTML/JSON can change, so parsing is isolated and any invalid or implausible response is rejected.
- Price mode is an environment setting so reviewers can test primary failure and full outage without changing deployed code.

## Gaps and constraints

The local machine had no PostgreSQL or Docker service and Packagist DNS was unavailable, so backend dependencies and integration tests could not be executed here. The implementation targets the official Laravel 13 skeleton and includes repeatable setup and tests for a normal CI or developer environment. No authentication, real payment, custody, or admin tools were added, as required by the brief.
