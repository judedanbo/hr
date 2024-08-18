<?php

namespace App\Enums;

enum DocumentTypeEnum: string
{
    case AcademicCertificate = 'A';
    case ProfessionalCertificate = 'P';
    case Transcript = 'T';
    case CurriculumVitae = 'C';
    case CoverLetter = 'L';
    case RecommendationLetter = 'R';
    case BirthCertificate = 'B';
    case Testimonial = 'N';
    case Other = 'O';

    public function getDocumentType(): string
    {
        return match ($this) {
            self::AcademicCertificate => 'Academic Certificate',
            self::ProfessionalCertificate => 'Professional Certificate',
            self::Transcript => 'Transcript',
            self::CurriculumVitae => 'Curriculum Vitae',
            self::CoverLetter => 'Cover Letter',
            self::RecommendationLetter => 'Letter of Recommendation ',
            self::BirthCertificate => 'Birth Certificate',
            self::Testimonial => 'Testimonial',
            self::Other => 'Others',
        };
    }
}
