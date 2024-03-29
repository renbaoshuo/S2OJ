<?php

class UOJTime {
	public static ?DateTime $time_now = null;
	public static string $time_now_str = '';

	const FORMAT = 'Y-m-d H:i:s';
	const FORMAT_CURRENT_YEAR = 'm-d H:i:s';
	const FORMAT_NOT_CURRENT_YEAR = 'Y-m-d H:i';
	const MAX_TIME = '9999-12-31 23:59:59';

	public static function init() {
		self::$time_now = new DateTime();
		self::$time_now_str = self::$time_now->format(static::FORMAT);
	}

	public static function str2time($input) {
		if (!is_string($input)) {
			return null;
		}
		try {
			$time = new DateTime($input);
			if ($time->format(static::FORMAT) !== $input) {
				return null;
			}
			return $time;
		} catch (Exception $e) {
			return null;
		}
	}

	public static function time2str(DateTime $time) {
		return $time->format(static::FORMAT);
	}

	/**
	 * @var DateTime|string $time
	 */
	public static function userFriendlyFormat($time) {
		try {
			if (!($time instanceof DateTime)) {
				$time = new DateTime($time);
			}

			if ($time->format('Y') === self::$time_now->format('Y')) {
				return $time->format(static::FORMAT_CURRENT_YEAR);
			} else {
				return $time->format(static::FORMAT_NOT_CURRENT_YEAR);
			}
		} catch (Exception $e) {
			return '';
		}
	}
}
