<?php

namespace App\State\Resolver;

use App\Attribute\ApiRequest; // Your custom attribute
use App\Exception\ValidationException; // Custom exception for validation errors
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;

final readonly class ApiRequestArgumentResolver implements ValueResolverInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {

    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $attributes = $argument->getAttributes(ApiRequest::class);

        if (empty($attributes)) {
            // If the attribute is not present, this resolver doesn't apply to this argument.
            // Yield nothing to let Symfony try the next resolver.
            return; // Or yield from [] if you want to be explicit
        }

        /** @var ApiRequest $attribute */
        $attribute = $attributes[0]; // Get your custom attribute
        $argumentType = $argument->getType();

        if ($argumentType === null) {
            throw new \LogicException(sprintf('Argument "%s" must have a type-hint.', $argument->getName()));
        }

        $dto = new $argumentType();

        // 1. Deserialize the request content into the DTO
        if ($attribute->deserialize) {
            $content = $request->getContent();
            try {
                // Use the serializer to populate the DTO from JSON
                $dto = $this->serializer->deserialize($content, $argumentType, 'json', ['object_to_populate' => $dto]);
            } catch (\Throwable $e) {
                // Handle deserialization errors (e.g., malformed JSON)
                throw new ValidationException('Invalid JSON payload.', ['payload' => $e->getMessage()]);
            }
        }

        // 2. Validate the DTO
        if ($attribute->validate) {
            $violations = $this->validator->validate($dto);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = [
                        'property' => $violation->getPropertyPath(),
                        'value' => $violation->getInvalidValue(),
                        'message' => $violation->getMessage(),
                    ];
                }
                throw new ValidationException('Validation failed.', $errors);
            }
        }

        yield $dto; // Yield the DTO to the controller method
    }
}
