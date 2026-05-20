<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Bootstrap
 * Path: public/clinical/app/bootstrap.php
 *
 * Central include file for shared clinical app dependencies.
 *
 * This file should be loaded by page/controller files instead of requiring
 * db.php, auth.php, csrf.php, helpers.php, repositories, services and validators
 * individually.
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

require_once __DIR__ . '/helpers.php';

/*
|--------------------------------------------------------------------------
| Repositories
|--------------------------------------------------------------------------
|
| These are loaded conditionally because some of them may not exist yet while
| the app is being refactored in stages.
|
*/

$repositoryFiles = [
    __DIR__ . '/repositories/AuditRepository.php',
    __DIR__ . '/repositories/UserRepository.php',
    __DIR__ . '/repositories/PatientRepository.php',
    __DIR__ . '/repositories/TreatmentRepository.php',
];

foreach ($repositoryFiles as $repositoryFile) {
    if (file_exists($repositoryFile)) {
        require_once $repositoryFile;
    }
}

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$serviceFiles = [
    __DIR__ . '/services/AuditService.php',
    __DIR__ . '/services/AuthService.php',
    __DIR__ . '/services/PatientService.php',
    __DIR__ . '/services/TreatmentService.php',
];

foreach ($serviceFiles as $serviceFile) {
    if (file_exists($serviceFile)) {
        require_once $serviceFile;
    }
}

/*
|--------------------------------------------------------------------------
| Validators
|--------------------------------------------------------------------------
*/

$validatorFiles = [
    __DIR__ . '/validators/PatientValidator.php',
    __DIR__ . '/validators/TreatmentValidator.php',
];

foreach ($validatorFiles as $validatorFile) {
    if (file_exists($validatorFile)) {
        require_once $validatorFile;
    }
}

/*
|--------------------------------------------------------------------------
| Factory helpers
|--------------------------------------------------------------------------
|
| Manual dependency creation. This gives us most of the benefit of dependency
| injection without introducing a framework or service container.
|
*/

function clinical_pdo(): PDO
{
    return clinical_db();
}

function clinical_audit_repository(): AuditRepository
{
    return new AuditRepository(clinical_pdo());
}

function clinical_audit_service(): AuditService
{
    return new AuditService(clinical_audit_repository());
}

function clinical_patient_repository(): PatientRepository
{
    return new PatientRepository(clinical_pdo());
}

function clinical_patient_validator(): PatientValidator
{
    return new PatientValidator();
}

function clinical_patient_service(): PatientService
{
    return new PatientService(
        clinical_patient_repository(),
        clinical_patient_validator(),
        clinical_audit_service()
    );
}

function clinical_treatment_repository(): TreatmentRepository
{
    return new TreatmentRepository(clinical_pdo());
}

function clinical_treatment_validator(): TreatmentValidator
{
    return new TreatmentValidator();
}

function clinical_treatment_service(): TreatmentService
{
    return new TreatmentService(
        clinical_treatment_repository(),
        clinical_patient_repository(),
        clinical_treatment_validator(),
        clinical_audit_service()
    );
}

function clinical_user_repository(): UserRepository
{
    return new UserRepository(clinical_pdo());
}

function clinical_auth_service(): AuthService
{
    return new AuthService(
        clinical_user_repository(),
        clinical_audit_service()
    );
}
