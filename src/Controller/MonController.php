<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

class MonController extends AbstractController
{
    private $filterService;

    public function __construct(FilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    //Permet d'uploader au moins une image
    /**
     * @Route("/api/upload", name="image_upload", methods={"POST"})
     */
    public function charge(Request $request)
    {
        /**
         *@var UploadedFile $file
         */

        $em = $this->getDoctrine()->getManager();

        $files = $request->files->get('photo');

        if (is_array($files) == true) {
            foreach ($files as $file) {
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $filename = str_replace(" ", "_", $filename);
                $filename = uniqid() . "." . $file->getClientOriginalExtension();
                $file->move(
                    $this->getParameter('image_directory'),
                    $filename
                );
            }
        } else
            $filename = pathinfo($files->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = str_replace(" ", "_", $filename);

        $filename .= uniqid() . "." . $files->getClientOriginalExtension();

        $files->move(
            'image_directory',
            $filename
        );


        $imagine = $this->filterService->getUrlOfFilteredImage("image_directory/" . $filename, 'patate');

        $table = [];
        $post = new Post();
        $post->setImage($imagine)
            ->setDatedecreation(new \DateTime());
        $em->persist($post);
        $em->flush();

        $table[] =
            [
                'post' => $post,

            ];

        return $this->json($table, 200, [], []);
    }


    //Simple post
    /**
     * @Route("/api/post", name="post_simple",methods={"POST"})
     */
    public function poster(Request $request)
    {
        $data = json_decode(
            $request->getContent(),
            true
        );
        $userid = $data['id'];
        $text = $data['text'];

        $em = $this->getDoctrine()->getManager();


        $user = $em->getRepository(User::class)->Find($userid);

        $table = [];
        $post = new Post();
        $post->setPost($text)
            ->setUsers($user)
            ->setDatedecreation(new \DateTime());
        $user->addPost($post);
        $em->persist($post);
        $em->flush();

        return new JsonResponse(
            [

                'post' => $post->getPost(),
                'Nom'  => $user->getNom(),
            ]
        );
    }
}
