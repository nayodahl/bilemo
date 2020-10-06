<?php

namespace App\Controller;

use App\Entity\Reseller;
use App\Repository\ResellerRepository;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ResellerController extends AbstractController
{
    /**
     * Get the detail of a reseller.
     *
     * @Route("/api/resellers/{resellerId}", methods="GET", name="app_reseller")
     * @OA\Get(
     *      path="/api/resellers/{resellerId}",
     *      tags={"reseller"},
     *      summary="Find reseller by ID",
     *      description="Returns a single reseller",
     *      @OA\Parameter(
     *          name="resellerId",
     *          in="path",
     *          description="ID of reseller to return",
     *          required=true,
     *          @OA\Schema(
     *              type="integer",
     *              format="int"
     *               )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Reseller"),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Reseller not found"
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Invalid ID supplier"
     *     )
     * )
     */
    public function showReseller(int $resellerId, ResellerRepository $resellerRepo): JsonResponse
    {
        $reseller = $resellerRepo->find($resellerId);
        if (null !== $reseller) {
            return $this->json($reseller, 200, [], ['groups' => 'show_resellers']);
        }

        return $this->json(['message' => 'this reseller does not exist'], 404);
    }

    /**
     * Get the list of all resellers.
     *
     * @Route("/api/resellers", methods="GET", name="app_resellers")
     * @OA\Get(
     *      path="/api/resellers",
     *      tags={"reseller"},
     *      summary="Find all resellers",
     *      description="Returns a list of all resellers",
     *      @OA\Response(
     *          response="200",
     *          description="successful operation",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="there is no reseller for the moment"
     *      ),
     * )
     */
    public function showResellers(ResellerRepository $resellerRepo): JsonResponse
    {
        $resellers = $resellerRepo->findAll();
        if (null !== $resellers) {
            return $this->json($resellers, 200, [], ['groups' => 'show_resellers']);
        }

        return $this->json(['message' => 'there is no reseller for the moment'], 404);
    }

    /**
     * @Route ("/api/auth/signin", name = "api_signin", methods = "POST")
     * @OA\Post(
     *      path="/api/auth/signin",
     *      tags={"login and signin"},
     *      summary="Signin to BileMo API",
     *      description="Signin to BileMo API",
     *      @OA\RequestBody(
     *         description="enter your email and password of your choice",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     description="enter your email as identifier, it must not be already taken",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     description="enter your chosen password, minimum 8 characters, one maj, one min, one number and one special character among #?!@$ %^&*-).",
     *                     type="string"
     *                 ),
     *                 example={"email": "exemple@mymail.com", "password": "mychosenpassword"}
     *             ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response="201",
     *          description="successful operation",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="input is not valid"
     *      ),
     * )
     */
    public function register(UserPasswordEncoderInterface $passwordEncoder, Request $request, ValidatorInterface $validator)
    {
        $encoder = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoder);

        $data = $request->getContent();

        $reseller = $serializer->deserialize($data, Reseller::class, 'json');

        // check if input data is valid (email valid and unique, password complex enough)
        $errors = $validator->validate($reseller);
        if (count($errors) > 0) {
            return $this->json(['message' => $errors], 400);
        }

        // encode password
        $reseller->setPassword($passwordEncoder->encodePassword($reseller, $reseller->getPassword()));

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($reseller);
        $manager->flush();

        return $this->json(['result' => 'You registered as a Reseller with success'], 201);
    }

    /**
     * @OA\Post(
     *      path="/api/auth/login",
     *      tags={"login and signin"},
     *      summary="Login to BileMo API to get your token",
     *      description="This can only be done by a signed in reseller, it will let you obtain a token.",
     *      @OA\RequestBody(
     *         description="enter your credentials",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="username",
     *                     description="enter the email you registred with as identifier",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     description="enter your password",
     *                     type="string"
     *                 ),
     *                 example={"username": "exemple@mymail.com", "password": "mypassword"}
     *             ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="successful operation",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="invalid credentials"
     *      ),
     * )
     */
}
