<?php

namespace App\Enums;

enum CalendarParticipantRole: string
{
	case MEMBER = 'member';
	case ADMIN = 'admin';
	case OWNER = 'owner';

	public static function toArray(): array
	{
		return array_column(CalendarParticipantRole::cases(), 'value');
	}
}
