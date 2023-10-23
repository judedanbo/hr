<?php

namespace App\Enums;

enum DocumentStatusEnum: string
{
  case Draft = 'D';
  case Pending = 'P';
  case Approved = 'A';
  case Rejected = 'R';
  case Cancelled = 'C';
  case Archived = 'X';

  public function getDocumentStatus(): string
  {
    return match ($this) {
      self::Draft => 'Draft',
      self::Pending => 'Pending',
      self::Approved => 'Approved',
      self::Rejected => 'Rejected',
      self::Cancelled => 'Cancelled',
      self::Archived => 'Archived',
    };
  }
}
