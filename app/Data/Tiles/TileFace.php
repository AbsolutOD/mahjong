<?php

namespace App\Data\Tiles;

use App\Enums\Dragon;
use App\Enums\FaceTone;
use App\Enums\Suit;
use App\Enums\TileType;
use App\Enums\Wind;
use App\Mahjong\AmericanMahjong;

/**
 * Everything needed to draw one tile, derived once so the view only draws.
 *
 * A face either resolves to a physical {@see Tile} or it does not, and that one
 * distinction carries both rules the design rests on (issue #8):
 *
 * - **Grey means unbound.** A slot whose suit variable is unassigned takes the
 *   `Unbound` tone and no tile, so it shows neither suit colour nor artwork.
 *   A half-resolved slot — suit known, number still a variable — takes the
 *   suit's tone but still has no tile, so it stays honest about the number.
 * - **Never colour alone.** Every face carries a corner index, and the honours
 *   also carry a glyph and a word band, so no two faces differ by tone alone.
 */
readonly class TileFace
{
    /**
     * @param  ?Tile  $tile  the tile this face resolved to, or null while a variable is still open
     * @param  FaceTone  $tone  the colour to paint in; `Unbound` is grey and means no suit is chosen
     * @param  string  $index  the permanent playing-card corner index
     * @param  string  $name  the accessible name, spoken in place of the artwork
     * @param  ?string  $well  the letter an unresolved slot shows in its empty well
     */
    private function __construct(
        public ?Tile $tile,
        public FaceTone $tone,
        public string $index,
        public string $name,
        public ?string $well = null,
    ) {
        //
    }

    /**
     * Build the face of a physical tile, which is always fully resolved.
     */
    public static function of(Tile $tile): self
    {
        return match (true) {
            $tile->suit !== null && $tile->number !== null => new self(
                $tile,
                FaceTone::forSuit($tile->suit),
                $tile->number.self::suitLetter($tile->suit),
                "{$tile->number} {$tile->suit->label()}",
            ),
            $tile->wind !== null => new self(
                $tile,
                FaceTone::Wind,
                $tile->wind->symbol(),
                "{$tile->wind->label()} Wind",
            ),
            $tile->dragon !== null => new self(
                $tile,
                match ($tile->dragon) {
                    Dragon::Red => FaceTone::Red,
                    Dragon::Green => FaceTone::Green,
                    Dragon::White => FaceTone::Soap,
                },
                match ($tile->dragon) {
                    Dragon::Red => 'RD',
                    Dragon::Green => 'GD',
                    Dragon::White => 'SO',
                },
                $tile->dragon->label(),
            ),
            $tile->type === TileType::Flower => new self($tile, FaceTone::Flower, 'FL', 'Flower'),
            default => new self($tile, FaceTone::Joker, 'JK', 'Joker'),
        };
    }

    /**
     * Build the face of a card slot under the given suit assignment.
     *
     * The slot resolves only as far as the assignment allows: a suited spec on
     * an unbound variable stays grey, and a number still standing on a variable
     * keeps its empty well however well its suit is known.
     */
    public static function for(TileSpec $spec, SuitAssignment $assignment): self
    {
        $variable = $spec->suitVariable();
        $suit = $variable === null ? null : $assignment->for($variable);

        return match (true) {
            $spec instanceof ZeroTile => self::zero(),
            $spec instanceof NumberTile => self::number($spec, $variable, $suit),
            $spec instanceof DragonTile => $suit === null
                ? self::unresolved(FaceTone::Unbound, 'D', "D{$variable}", "Dragon in suit {$variable}")
                : self::of(Tile::dragon(AmericanMahjong::dragonForSuit($suit))),
            $spec instanceof WindTile => self::of(Tile::wind($spec->wind)),
            default => self::of(Tile::flower()),
        };
    }

    /**
     * Determine whether this face has settled on a tile it can draw.
     */
    public function isResolved(): bool
    {
        return $this->tile !== null;
    }

    /**
     * Determine whether the card has chosen a suit for this face yet.
     */
    public function isBound(): bool
    {
        return $this->tone !== FaceTone::Unbound;
    }

    /**
     * Get the word printed under the artwork, where the artwork alone is a hue.
     *
     * Number tiles return null: their pips and numeral already say what they
     * are. The honours name themselves, which is what keeps the red and green
     * dragons apart for a viewer who cannot tell the two hues apart.
     */
    public function word(): ?string
    {
        return match (true) {
            $this->tile === null => null,
            $this->tile->wind !== null => strtoupper($this->tile->wind->label()),
            $this->tile->dragon !== null => match ($this->tile->dragon) {
                Dragon::Red => 'RED',
                Dragon::Green => 'GREEN',
                Dragon::White => 'SOAP',
            },
            $this->tile->type === TileType::Flower => 'FLOWER',
            $this->tile->type === TileType::Joker => 'JOKER',
            default => null,
        };
    }

    /**
     * Get the traditional character this face draws, where it draws one.
     *
     * The dots and bams are patterns rather than characters, so they have no
     * glyph; the craks draw 萬 beneath their numeral.
     */
    public function glyph(): ?string
    {
        return match (true) {
            $this->tile === null => null,
            $this->tile->suit !== null => $this->tile->suit === Suit::Craks ? '萬' : null,
            $this->tile->wind !== null => match ($this->tile->wind) {
                Wind::East => '東',
                Wind::South => '南',
                Wind::West => '西',
                Wind::North => '北',
            },
            $this->tile->dragon !== null => match ($this->tile->dragon) {
                Dragon::Red => '中',
                Dragon::Green => '發',
                Dragon::White => null,
            },
            default => null,
        };
    }

    /**
     * Build the face of a number slot, resolving as far as its suit allows.
     */
    private static function number(NumberTile $spec, string $variable, ?Suit $suit): self
    {
        $symbol = $spec->symbol();

        if ($suit === null) {
            return self::unresolved(FaceTone::Unbound, $symbol, $symbol.$variable, "{$symbol} in suit {$variable}");
        }

        if ($spec->number->isVariable()) {
            return self::unresolved(
                FaceTone::forSuit($suit),
                $symbol,
                $symbol.self::suitLetter($suit),
                "{$symbol} in {$suit->label()}",
            );
        }

        return self::of(Tile::number($suit, $spec->number->literal));
    }

    /**
     * Build the face of the soap standing in as the numeral zero.
     */
    private static function zero(): self
    {
        $soap = self::of(Tile::dragon(Dragon::White));

        return new self($soap->tile, $soap->tone, '0', 'Soap, standing in as zero');
    }

    /**
     * Build a face that has no tile to draw — an empty well and a letter.
     */
    private static function unresolved(FaceTone $tone, string $well, string $index, string $name): self
    {
        return new self(null, $tone, $index, $name, $well);
    }

    /**
     * Get the letter a suit contributes to a corner index.
     */
    private static function suitLetter(Suit $suit): string
    {
        return match ($suit) {
            Suit::Dots => 'D',
            Suit::Bams => 'B',
            Suit::Craks => 'C',
        };
    }
}
