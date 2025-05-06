<?php
namespace VitaminadaSport\Validation;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use VitaminadaSport\Logging\Logger;

class Validator {
    private $validator;
    private $logger;

    public function __construct() {
        $this->validator = Validation::createValidator();
        $this->logger = Logger::getInstance();
    }

    public function validatePaymentData(array $data): array {
        $constraints = new Assert\Collection([
            'monto' => [
                new Assert\NotBlank(),
                new Assert\Positive(),
                new Assert\Range(['min' => 1])
            ],
            'telefono' => [
                new Assert\NotBlank(),
                new Assert\Regex('/^[0-9]{11}$/')
            ],
            'cedula_rif' => [
                new Assert\NotBlank(),
                new Assert\Regex('/^[VEJPGvejpg]{1}[0-9]{8,9}$/')
            ],
            'banco_destino' => [
                new Assert\NotBlank(),
                new Assert\Choice(['banco_de_venezuela', 'banesco', 'mercadobanco'])
            ],
            'concepto' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 100])
            ]
        ]);

        $violations = $this->validator->validate($data, $constraints);
        
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
                $this->logger->warning("Validación fallida: {$violation->getPropertyPath()} - {$violation->getMessage()}", ['data' => $data]);
            }
            return ['success' => false, 'errors' => $errors];
        }

        return ['success' => true];
    }

    public function validateScheduleData(array $data): array {
        $constraints = new Assert\Collection([
            'dia' => [
                new Assert\NotBlank(),
                new Assert\Choice(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])
            ],
            'hora_inicio' => [
                new Assert\NotBlank(),
                new Assert\Time()
            ],
            'hora_fin' => [
                new Assert\NotBlank(),
                new Assert\Time()
            ]
        ]);

        $violations = $this->validator->validate($data, $constraints);
        
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
                $this->logger->warning("Validación fallida: {$violation->getPropertyPath()} - {$violation->getMessage()}", ['data' => $data]);
            }
            return ['success' => false, 'errors' => $errors];
        }

        // Validar que hora_fin sea mayor que hora_inicio
        if (strtotime($data['hora_inicio']) >= strtotime($data['hora_fin'])) {
            $errors['hora_fin'] = 'La hora de fin debe ser posterior a la hora de inicio';
            return ['success' => false, 'errors' => $errors];
        }

        return ['success' => true];
    }

    public function validateReservationData(array $data): array {
        $constraints = new Assert\Collection([
            'horario_id' => [
                new Assert\NotBlank(),
                new Assert\Type('integer')
            ],
            'fecha' => [
                new Assert\NotBlank(),
                new Assert\Date()
            ]
        ]);

        $violations = $this->validator->validate($data, $constraints);
        
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
                $this->logger->warning("Validación fallida: {$violation->getPropertyPath()} - {$violation->getMessage()}", ['data' => $data]);
            }
            return ['success' => false, 'errors' => $errors];
        }

        return ['success' => true];
    }
}
