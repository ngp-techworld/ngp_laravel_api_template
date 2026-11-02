# 0001: ULID primary keys in upper case

## Context

`ngp-general` §8 forbids sequential ids in public URLs for things users must not enumerate, and the
starter API contract says users and items have ULID public ids (`01JC...`). Keeping a bigint key
plus a separate public ULID column means two ids per row and a chance of leaking the wrong one.

## Decision

Models use ULIDs as their primary key (`$table->ulid('id')->primary()`, `foreignUlid`,
`ulidMorphs` for Sanctum tokens) through `App\Models\Concerns\HasUppercaseUlids`, which keeps
Laravel's `HasUlids` but generates the canonical upper-case form. Laravel's default lower-cases them.

## Consequences

- One id per row, safe to expose; ULIDs sort by creation time, so `orderByDesc('id')` is a stable
  tie-break for equal timestamps.
- Ids are 26-character strings (`char(26)`), slightly larger indexes than bigint.
- New models must use `HasUppercaseUlids` and ULID foreign keys.
