<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case GestionnaireParc = 'gestionnaire_parc';
    case Chauffeur = 'chauffeur';
    case GaragisteAgree = 'garagiste_agree';
    case Auditeur = 'auditeur';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::GestionnaireParc => 'Gestionnaire de parc',
            self::Chauffeur => 'Chauffeur',
            self::GaragisteAgree => 'Garagiste agréé',
            self::Auditeur => 'Auditeur / Acheteur',
        };
    }

    public function canManageFleet(): bool
    {
        return in_array($this, [self::SuperAdmin, self::GestionnaireParc], true);
    }

    public function canManageUsers(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canCertifyMaintenance(): bool
    {
        return in_array($this, [self::SuperAdmin, self::GaragisteAgree], true);
    }

    public function canReportMileage(): bool
    {
        return in_array($this, [
            self::SuperAdmin,
            self::Chauffeur,
            self::GaragisteAgree,
        ], true);
    }

    public function canUploadDocuments(): bool
    {
        return $this->canManageFleet();
    }

    public function isReadOnly(): bool
    {
        return $this === self::Auditeur;
    }
}
