<?php

namespace Intensa\Logger\Notification\Database;

use Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\DatetimeField,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class AlertTable extends DataManager
{
    public static function getTableName()
    {
        return 'intensa_logger_alert';
    }

    public static function getMap()
    {
        return [
            new IntegerField(
                'id',
                [
                    'primary' => true,
                    'autocomplete' => true,
                ]
            ),
            new StringField(
                'message_hash',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateMessageHash'],
                ]
            ),
            new DatetimeField(
                'create_at',
                [
                    'required' => true,
                ]
            ),
        ];
    }

    /**
     * Returns validators for message_hash field.
     *
     * @return array
     */
    public static function validateMessageHash(): array
    {
        return [
            new LengthValidator(null, 32),
        ];
    }
}