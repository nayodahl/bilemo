<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Knp\Component\Pager\PaginatorInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerController extends AbstractController
{
    /**
     * Get the detail of a customer of a logged reseller.
     *
     * @Route("/api/v1/customers/{customerId}", methods="GET", name="app_customer")
     * @OA\Get(
     *      path="/api/v1/customers/{customerId}",
     *      tags={"customer"},
     *      summary="Find customer by Id",
     *      description="Returns a single customer detail, you need to be an authenticated reseller",
     *      @OA\Parameter(
     *          name="customerId",
     *          in="path",
     *          description="ID of customer to return",
     *          required=true,
     *          @OA\Schema(
     *              type="integer",
     *              format="int"
     *               )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Customer"),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="customer not found"
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Invalid ID supplier"
     *     )
     * )
     */
    public function showCustomer(int $customerId, CustomerRepository $customerRepo, Security $security, SerializerInterface $serializer): Response
    {
        $customer = $customerRepo->findOneCustomerofOneReseller($security->getUser()->getId(), $customerId);
        if (null !== $customer) {
            $json = $serializer->serialize($customer, 'json', SerializationContext::create()->enableMaxDepthChecks());

            return new Response($json, 200, ['Content-Type' => 'application/json']);
        }

        return $this->json(['message' => 'this customer does not exist, or is not yours'], 404);
    }

    /**
     * Get the list of all customers of a logged reseller.
     *
     * @Route("/api/v1/customers/{page<\d+>?1}", methods="GET", name="app_customers")
     * @OA\Get(
     *      path="/api/v1/customers",
     *      tags={"customer"},
     *      summary="Find all your customers",
     *      description="Returns a paginated list of all your customers, you need to be an authenticated reseller. The list of results is paginated, so if you need next page, add the page number as parameter in the query. Exemple : /api/v1/customers?page=2 ",
     *      @OA\Response(
     *          response="200",
     *          description="successful operation",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="there is no customer for the moment"
     *      ),
     * )
     */
    public function showCustomers(CustomerRepository $customerRepo, Security $security, Request $request, SerializerInterface $serializer, PaginatorInterface $paginator): Response
    {
        //get data
        $customers = $customerRepo->findAllCustomersofOneReseller($security->getUser()->getId());

        // pagination
        $paginated = $paginator->paginate(
            $customers,
            $request->query->getInt('page', 1), /*page number*/
            $this->getParameter('pagination_limit') /*limit per page*/
        );

        if (null !== $customers) {
            $json = $serializer->serialize($paginated, 'json', SerializationContext::create()->enableMaxDepthChecks());

            return new Response($json, 200, ['Content-Type' => 'application/json']);
        }

        return $this->json(['message' => 'there is no customer for the moment'], 404);
    }

    /**
     * Create a new customer.
     *
     * @Route("/api/v1/customers", methods="POST", name="app_create_customer")
     * @OA\Post(
     *      path="/api/v1/customers",
     *      tags={"customer"},
     *      summary="Creates a new customer",
     *      description="Creates a new customer linked to your account, you need to be an authenticated reseller",
     *      @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="firstname",
     *                     description="enter the firstname of the customer",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="lastname",
     *                     description="enter the lastname of the customer",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     description="enter the email of the customer",
     *                     type="string"
     *                 ),
     *                 example={"firstname": "Emily", "lastname": "Cooper", "email": "emily.cooper@mymail.com"}
     *             ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response="201",
     *          description="customer created"
     *      ),
     *      @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *      ),
     * )
     */
    public function CreateCustomer(Request $request, ValidatorInterface $validator, Security $security, SerializerInterface $serializer): JsonResponse
    {
        $reseller = $security->getUser();

        $customer = $serializer->deserialize($request->getContent(), Customer::class, 'json');
        $customer->setReseller($reseller);

        $errors = $validator->validate($customer);
        if (count($errors) > 0) {
            return $this->json(['message' => $errors], 400);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($customer);
        $em->flush();

        return $this->json(['message' => 'customer created'], 201);
    }

    /**
     * Delete a customer.
     *
     * @Route("/api/v1/customers/{customerId}", methods="DELETE", name="app_delete_customer")
     * @OA\Delete(
     *      path="/api/v1/customers/{customerId}",
     *      tags={"customer"},
     *      summary="Deletes a customer",
     *      description="Delete a customer linked to your account, you need to be an authenticated reseller",
     *      @OA\Parameter(
     *         name="customerId",
     *         in="path",
     *         description="Customer Id to delete",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int"
     *         ),
     *      ),
     *      @OA\Response(
     *          response="204",
     *          description="Delete a customer"
     *      ),
     *      @OA\Response(
     *         response=404,
     *         description="Customer not found",
     *      ),
     * )
     */
    public function DeleteCustomer(int $customerId, CustomerRepository $customerRepo, Security $security): JsonResponse
    {
        $reseller = $security->getUser();
        $customer = $customerRepo->find($customerId);

        if (null === $customer) {
            return $this->json(['message' => 'customer does not exist'], 404);
        }

        // check if the logged reseller is the one that owns the customer
        if ($reseller !== $customer->getReseller()) {
            return $this->json(['message' => 'you are not authorized to delete this customer'], 403);
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($customer);
        $em->flush();

        return $this->json(['message' => 'customer has been deleted'], 204);
    }
}
