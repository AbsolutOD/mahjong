<?php

namespace App\Data\Tiles;

use App\Enums\Dragon;
use App\Enums\Suit;
use App\Enums\TileType;
use App\Enums\Wind;
use App\Mahjong\AmericanMahjong;
use InvalidArgumentException;

/**
 * A physical tile — the thing sitting on the rack.
 *
 * This is the counterpart to {@see TileSpec}: a spec is what the card asks for
 * and may still be waiting on a variable, whereas a tile is always a settled
 * face you could pick up. Only a tile can be drawn without qualification.
 */
readonly class Tile
{
    /**
     * The lowest and highest number a suited tile carries.
     */
    public const int MINIMUM_NUMBER = 1;

    public const int MAXIMUM_NUMBER = 9;

    private function __construct(
        public TileType $type,
        public ?Suit $suit = null,
        public ?int $number = null,
        public ?Wind $wind = null,
        public ?Dragon $dragon = null,
    ) {
        //
    }

    /**
     * Build a suited number tile.
     */
    public static function number(Suit $suit, int $number): self
    {
        throw_unless(
            $number >= self::MINIMUM_NUMBER && $number <= self::MAXIMUM_NUMBER,
            new InvalidArgumentException("[{$number}] is not a tile number; suited tiles run 1 through 9.")
        );

        return new self(TileType::Number, suit: $suit, number: $number);
    }

    /**
     * Build one of the four winds.
     */
    public static function wind(Wind $wind): self
    {
        return new self(TileType::Wind, wind: $wind);
    }

    /**
     * Build one of the three dragons; the white dragon is the soap.
     */
    public static function dragon(Dragon $dragon): self
    {
        return new self(TileType::Dragon, dragon: $dragon);
    }

    /**
     * Build a flower. All eight are interchangeable, so they share one face.
     */
    public static function flower(): self
    {
        return new self(TileType::Flower);
    }

    /**
     * Build a joker.
     */
    public static function joker(): self
    {
        return new self(TileType::Joker);
    }

    /**
     * Get every distinct face in the American set, copies aside.
     *
     * The order is the order a set is laid out for study: suits by number,
     * then winds, dragons, flowers and the joker.
     *
     * @return list<self>
     */
    public static function all(): array
    {
        $tiles = [];

        foreach (Suit::cases() as $suit) {
            foreach (range(self::MINIMUM_NUMBER, self::MAXIMUM_NUMBER) as $number) {
                $tiles[] = self::number($suit, $number);
            }
        }

        foreach (Wind::cases() as $wind) {
            $tiles[] = self::wind($wind);
        }

        foreach (Dragon::cases() as $dragon) {
            $tiles[] = self::dragon($dragon);
        }

        $tiles[] = self::flower();
        $tiles[] = self::joker();

        return $tiles;
    }

    /**
     * Get the code that identifies this face in the tile inventory.
     *
     * @see AmericanMahjong::tileInventory()
     */
    public function code(): string
    {
        return match ($this->type) {
            TileType::Number => "{$this->suit->value}-{$this->number}",
            TileType::Wind => "wind-{$this->wind->value}",
            TileType::Dragon => "dragon-{$this->dragon->value}",
            default => $this->type->value,
        };
    }
}
