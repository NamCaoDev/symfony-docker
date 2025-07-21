<?php

namespace App\Controller\Auth;

use App\Api\UserRegisterInput;
use App\Attribute\ApiRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[OA\Tag(name: 'Authentication')]
#[Route('/api', name: 'api_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em,
    )
    {

    }

    #[Route('/login', name: 'login', methods: ['POST'])]
     #[OA\Post(
        summary: 'User login to get JWT token',
        description: 'Authenticates a user with username and password, and returns a JWT token.',
        requestBody: new OA\RequestBody( // <--- This defines the request body
            required: true,
            description: 'User credentials',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        example: 'john.doe@example.com' // <--- Example for username
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password', // Hints to UI it's a password field
                        example: 'secretpassword' // <--- Example for password
                    ),
                ],
                type: 'object',
                example: [ // <--- Overall body example (optional, but good for clarity)
                    'email' => 'testuser@gmail.com',
                    'password' => 'testpass',
                ]
            )
        ),
        responses: [ // <--- Define possible responses for documentation
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful authentication and JWT token generation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: 'john.doe@example.com'),
                        new OA\Property(property: 'token', type: 'string', example: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE2NzgwODk2MDAsImV4cCI6MTY3ODA5MzIwMCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiam9obi5kb2VAZXhhbXBsZS5jb20ifQ.signature'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'Invalid credentials or missing input',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid credentials.'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function login(#[CurrentUser] ?User $user): Response
    {
         if (null === $user) {
             return $this->json([
                 'message' => 'missing credentials',
             ], Response::HTTP_UNAUTHORIZED);
         }

         // If the user is authenticated (meaning $user is not null), generate the token
         $token = $this->jwtManager->create($user);

          return $this->json([
             'user'  => $user->getUserIdentifier(),
             'token' => $token,
          ]);
    }


    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\Post(
        summary: 'Register a new user and get JWT token',
        description: 'Creates a new user account with provided email and password, and automatically logs them in by returning a JWT token.',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'User registration data',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'new_user@example.com', description: 'User\'s email address (must be valid and unique)'),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        example: 'SecureP@ss1',
                        minLength: 8,
                        description: 'User\'s chosen password. Must be at least 8 characters long, contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
                    ),
                ],
                type: 'object',
                required: ['email', 'password']
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'User registered successfully and logged in. Returns JWT token.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User registered successfully!'),
                        new OA\Property(property: 'id', type: 'integer', example: 123),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'new_user@example.com'),
                        new OA\Property(property: 'token', type: 'string', example: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Invalid input or validation errors',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'property', type: 'string', example: 'password'),
                                    new OA\Property(property: 'value', type: 'string', example: 'weakpass'),
                                    new OA\Property(property: 'message', type: 'string', example: 'Your password must contain at least one uppercase letter.'),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: Response::HTTP_CONFLICT,
                description: 'User with this email already exists',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User with this email already exists.'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    // IMPORTANT: Use the #[ApiRequest] attribute and type-hint your DTO
    public function register(#[ApiRequest] UserRegisterInput $input): JsonResponse
    {
        // Manual validation checks removed as they are now handled by the resolver
        // 1. Check if user already exists (database-level unique check - still needed here)
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $input->email]);
        if ($existingUser) {
            return $this->json(['message' => 'User with this email already exists.'], Response::HTTP_CONFLICT);
        }

        // 2. Create new User entity
        $user = new User();
        $user->setEmail($input->email); // Get email from DTO
        $user->setRoles(['ROLE_USER']);

        // 3. Hash the password (get plain password from DTO)
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $input->password
        );
        $user->setPassword($hashedPassword);

        // 4. Persist to database
        $this->em->persist($user);
        $this->em->flush();

        // 5. Generate JWT token
        $token = $this->jwtManager->create($user);

        return $this->json([
            'message' => 'User registered successfully!',
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'token' => $token,
        ], Response::HTTP_CREATED);
    }

    #[Route(path: '/me', name: 'app_me', methods: ['GET'])]
    public function getProfile()
    {
        $currentUser = $this->getUser();

        if (null === $currentUser) {
            return $this->json([
                'message' => 'Not authenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
                'message' => 'Welcome, ' . $currentUser->getUserIdentifier() . '! You are logged in.',
                'user_identifier' => $currentUser->getUserIdentifier(),
        ], Response::HTTP_ACCEPTED);
    }
}
