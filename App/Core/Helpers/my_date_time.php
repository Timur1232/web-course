<?php namespace App\Core\Helpers;
use DateTime;

final class My_Date_Time {
    public const FORMAT = 'Ymd-His';

    public static function from_date(DateTime $d): string {
        return $d->format(self::FORMAT);
    }

    public static function now(): string {
        return self::from_date(new DateTime('now'));
    }

    public static function to_date(string $datestr): ?DateTime {
        $d = DateTime::createFromFormat(self::FORMAT, $datestr);
        return $d === false ? null : $d;
    }
}

// WARNING: Class using this trait must have field $datestr with type string
// TODO: Maybe add abstract methods for setting and getting $datestr to imply it's usage inside trait
trait MyDateTrait {
    public function get_date(): ?DateTime {
        if (!isset($this->datestr)) return null;
        return My_Date_Time::to_date($this->datestr);
    }

    public function with_date(DateTime $date): self {
        $this->datestr = My_Date_Time::from_date($date);
        return $this;
    }

    public function with_current_date(): self {
        $this->datestr = My_Date_Time::now();
        return $this;
    }

    final public const DEFAULT_DATE_FORMAT = 'd.m.Y H:i';
    public function format(string $fmt = self::DEFAULT_DATE_FORMAT): string {
        $date = $this->get_date();
        if (!isset($date)) return 'Unknown';
        return $date->format($fmt);
    }
}
