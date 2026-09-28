<?php

namespace App\Enums;

enum CardReportReason: string
{
    case Inappropriate = 'inappropriate';
    case Offensive = 'offensive';
    case Spam = 'spam';
    case Other = 'other';
}
