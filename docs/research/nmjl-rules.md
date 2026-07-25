# Research: American Mah Jongg (NMJL-style) rules the engine must encode

Resolves [issue #6](https://github.com/AbsolutOD/mahjong/issues/6). Sourcing note: the NMJL's
official FAQ page (https://www.nationalmahjonggleague.org/faq.aspx) is currently marked
"Under Construction" and contains no rule content, so findings are verified against
established references: the American Mah Jongg Association (AMJA) rulebook, Tom Sloper's
long-running NMJL FAQ (sloperama.com, widely treated as the authoritative unofficial
reference), I Love Mahj, and Wikipedia. Each claim carries the URL it was verified from.

## 1. Joker rules

- **Where jokers substitute:** a joker may represent any natural tile in groupings of
  **3 or more identical tiles** — pung (3), kong (4), quint (5), sextet (6). There is no
  minimum natural-tile requirement; a grouping may even be composed entirely of jokers.
  (https://guide.americanmahjonggassociation.com/how-to-play-american-mah-jongg/jokers-and-joker-exchange/)
- **Where jokers are forbidden:** never in **singles or pairs**
  (https://ilovemahj.com/american-mahjong-getting-started). Groups of 3+ *non-identical*
  tiles also forbid jokers: **NEWS** (four singles) and **year groups like "2026"** take no
  jokers because the tiles are not identical — this extends to any run-of-singles grouping
  such as "1234" or "2468" printed as singles.
  (https://sloperama.com/mjfaq/mjfaq19.html, https://sloperama.com/mjfaq/2026.html)
  Engine rule: joker eligibility attaches to the *group*, and the test is "3+ copies of one
  identical tile," not group length.
- **Joker exchange (redemption):** on your **own turn only**, after picking from the wall
  and racking (or calling a discard and completing the exposure), you may trade the
  matching natural tile from your hand for a joker sitting in **any player's exposure,
  including your own**. The exchange cannot be refused and multiple redemptions per turn
  are allowed.
  (https://guide.americanmahjonggassociation.com/how-to-play-american-mah-jongg/jokers-and-joker-exchange/,
  https://sloperama.com/mjfaq/mjfaq19.html)
- **Charleston:** jokers may **never** be passed in the Charleston or courtesy pass.
  (https://en.wikipedia.org/wiki/American_mahjong, https://ilovemahj.com/american-mahjong-getting-started)
- **Discarded jokers:** a discarded joker is permanently dead ("down is dead") — it can
  never be claimed for an exposure or for mah jongg; there is no "joker call."
  (https://guide.americanmahjonggassociation.com/how-to-play-american-mah-jongg/jokers-and-joker-exchange/,
  https://sloperama.com/mjfaq/mjfaq19.html)

## 2. Concealed vs exposed hands

- The card marks each hand **C** (concealed) or **X** (exposed-allowed).
  (https://sloperama.com/mjfaq/mjfaq19.html, https://mahjongcompare.com/nmjl-card/how-to-read)
- **C hands:** no exposures may be made at any point; a discard may be claimed **only**
  when it is the final tile completing mah jongg, at which point the whole hand is
  revealed. "You cannot call a tile for a concealed hand UNLESS this is the last tile you
  need to complete Mah Jongg."
  (https://ilovemahj.com/american-mahjong-getting-started, https://sloperama.com/mjfaq/2026.html)
- Joker **redemption is still allowed** with a concealed hand (it is not an exposure).
  (https://sloperama.com/mjfaq/mjfaq19.html)
- **X hands:** discards may be claimed, but only to immediately expose a complete
  pung/kong/quint/sextet of identical tiles (or for mah jongg). Pairs and singles can never
  be completed from a discard except the winning tile — engine-relevant for *all* hands,
  not just concealed ones.
  (https://sloperama.com/mjfaq/mjfaq19.html,
  https://southernsparrow.com/blogs/how-to-play-mahjong/mastering-singles-pairs-in-american-mahjong-rules-strategy)

## 3. The Charleston

Verified against the AMJA rulebook
(https://guide.americanmahjonggassociation.com/how-to-play-american-mah-jongg/the-charleston/)
and Wikipedia (https://en.wikipedia.org/wiki/American_mahjong):

- **First Charleston (mandatory):** three passes of exactly 3 tiles face-down, in order
  **right → across → left**.
- **Second Charleston (optional, all-or-nothing):** occurs only if no one objects — any
  player may cancel it for the whole table without explanation. Order reverses:
  **left → across → right**.
- **Blind pass:** permitted **only on the third (last) pass of each Charleston** — the
  first Charleston's *left* pass and the second Charleston's *right* pass. The passer may
  take 1, 2, or 3 of the tiles just received and pass them on unseen, topping up from hand
  to total exactly 3.
- **Courtesy pass:** after the Charleston(s), each player may exchange **0–3 tiles with
  the player opposite**; the count is set by whichever of the two wants to pass fewer
  tiles (mutual agreement).
- **Jokers are never passed** in any Charleston pass or the courtesy pass.
  (https://en.wikipedia.org/wiki/American_mahjong)

## 4. Card conventions

- **Colors = suit distinctness, not specific suits.** Lines are printed in blue/red/green.
  One color per line = all one suit; two colors = any **two different** suits; three
  colors = three different suits. The card never says red must be craks — the player
  chooses the concrete suit assignment, kept consistent within the line.
  (https://mahjongcompare.com/nmjl-card/how-to-read, https://sloperama.com/mjfaq/2026.html)
- **Suitless tiles** (flowers, winds, soaps-used-as-zero) are conventionally printed in
  blue and carry no suit constraint. (https://sloperama.com/mjfaq/2026.html)
- **Soap = zero:** the white dragon ("soap") is the only tile ever used as a numeral 0,
  and only where the card literally prints "0" (year hands like 2026); zeros never
  participate in consecutive runs. (https://sloperama.com/mjfaq/2026.html)
- **Flowers:** all 8 flower tiles are fully interchangeable regardless of the
  flower/season artwork. (https://sloperama.com/mjfaq/mjfaq19.html)
- **NEWS** = the four wind singles N, E, W, S — no jokers, no pre-win exposure.
  (https://sloperama.com/mjfaq/mjfaq19.html)
- **Dragons match suits:** when a colored `D` appears attached to a numbered/suited line,
  red dragon ↔ craks, green dragon ↔ bams, soap/white dragon ↔ dots. Card *ink* colors do
  not refer to dragon colors.
  (https://www.americanmahjonggassociation.com/how-to-read-the-nmjl-card,
  https://southernsparrow.com/pages/how-to-read-the-nmjl-card-nmjl-card)

## 5. Pattern shorthand

Verified from https://mahjongcompare.com/nmjl-card/how-to-read and
https://sloperama.com/mjfaq/2026.html:

- Lines are strings of space-separated groups, e.g. `FF 2026 2222 6666`: repeated symbols
  show group size — single (1 char), pair (2), pung (3), kong (4), quint (5), sextet (6,
  when a year's card uses them).
- Symbols: digits `1`–`9` = number tiles; `0` = soap acting as zero; `F` = any flower;
  `D` = dragon (suit-matched per color context; some notations also use `R`/`G`/`0` for
  red/green/white dragons explicitly); `N`/`E`/`W`/`S` = winds.
- The literal word "any" on the card overrides color inference and means any suit.
  (https://sloperama.com/mjfaq/mjfaq19.html)
- Categories on a typical card: Year, 2468 (evens), Any Like Numbers, Quints, Consecutive
  Run, 13579 (odds), Winds-Dragons, 369, Singles and Pairs.
  (https://ilovemahj.com/american-mahjong-getting-started)
- Each line carries a point value (25–50 range; harder hands score more) and a C/X flag.
  (https://mahjongcompare.com/nmjl-card/how-to-read)

## 6. Tile inventory (152 tiles)

Verified at https://en.wikipedia.org/wiki/American_mahjong and
https://sloperama.com/mjfaq/mjfaq19.html:

| Tiles | Count |
| --- | --- |
| Suit tiles: 3 suits (dots, bams, craks) × numbers 1–9 × 4 copies | 108 |
| Winds: 4 × N, E, W, S | 16 |
| Dragons: 4 red, 4 green, 4 white (soap) | 12 |
| Flowers (all interchangeable) | 8 |
| Jokers | 8 |
| **Total** | **152** |

## Other engine-relevant facts

- **Hand size:** players hold 13 tiles and win on a 14th; a winning hand must exactly
  match a card line — no generic "4 sets + pair" structure as in Asian variants.
  (https://ilovemahj.com/american-mahjong-getting-started,
  https://mahjong4friends.com/guides/mah-jongg-hands)
- **Quints mathematically require jokers:** only 4 copies of any tile exist, so a quint
  needs at least one joker (flowers are the one exception — 8 interchangeable copies).
  The "jokerless" score bonus never applies to Quints hands.
  (https://sloperama.com/mjfaq/mjfaq19.html)
- **Singles and Pairs category is jokerless by construction:** every group is size 1 or
  2, so jokers are impossible anywhere in those hands.
  (https://ilovemahj.com/american-mahjong-getting-started)
- **"Any Like Numbers" hands:** the same number across suits — the engine needs a "same
  number, distinct suits" variable, not a fixed digit.
  (https://ilovemahj.com/american-mahjong-getting-started)
- **The card changes annually**, so the schema should be year-versioned.
  (https://sloperama.com/mjfaq/2026.html)
- **Exposure atomicity:** claiming a discard commits you to immediately exposing the
  completed group; jokers in that exposure become redeemable by opponents — relevant if
  the engine ever models game state, not just matching.
  (https://guide.americanmahjonggassociation.com/how-to-play-american-mah-jongg/jokers-and-joker-exchange/)

## Implications for the pattern schema

Concrete flags/structures the schema and matching engine must support:

1. **Group-level structure:** each hand is an ordered list of groups; each group has a
   size (1–6), a tile spec, and derived properties below. Group sizes: single, pair,
   pung (3), kong (4), quint (5), sextet (6).
2. **Joker eligibility per group** — derived, not hand-level: a group accepts jokers iff
   it consists of 3+ copies of one *identical* tile. Singles, pairs, NEWS, year-digit
   runs, and any run-of-singles group are joker-ineligible even when length ≥ 3.
3. **Concealed flag per hand** (`concealed: true/false` from the card's C/X), plus
   engine-side exposure legality: only identical-tile groups of 3+ are exposable, and
   never on a concealed hand except the winning claim.
4. **Suit-permutation abstraction:** groups reference *suit variables* (e.g. suit A/B/C
   meaning "any distinct suits"), not concrete suits; the matcher must try all
   assignments of dots/bams/craks to the variables. A "suit count" of 1, 2, or 3 per
   line falls out of how many variables the line uses.
5. **Number variables:** "Any Like Numbers" and consecutive-run lines need a number
   variable (fixed offset relationships for runs) rather than literal digits.
6. **Soap-as-zero:** the white dragon must be addressable both as a dragon and as the
   numeral 0 in year-style groups; zeros never join consecutive runs.
7. **Suit-matched dragons:** a `D` in a suited line binds to the dragon of the assigned
   suit (red↔craks, green↔bams, soap↔dots), so dragon identity depends on the suit
   variable's assignment.
8. **Flower interchangeability:** model all 8 flowers as a single tile type `F`.
9. **Tile inventory constraints:** matching/quiz generation must respect max copies —
   4 per natural tile, 8 flowers, 8 jokers, 152 total; quints therefore imply jokers.
10. **Card/year versioning:** patterns belong to a card (year); values (25–50) and C/X
    flags are per-line attributes.
11. **Charleston support (assistant feature):** pass legality needs only one tile-level
    rule — jokers are unpassable; the pass structure (3×3 right/across/left, optional
    reversed second Charleston, blind pass on each Charleston's last pass, 0–3 courtesy
    pass across) is app flow, not schema.
