<?php

namespace App\Controller;

use App\DTO\ContactDTO;
use App\Entity\Contact;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $contactDTO = new ContactDTO();
        $form = $this->createForm(ContactType::class, $contactDTO);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Créer une nouvelle entité Contact à partir du DTO
            $contact = new Contact();
            $contact->setName($contactDTO->getName());
            $contact->setEmail($contactDTO->getEmail());
            $contact->setSubject($contactDTO->getSubject());
            $contact->setMessage($contactDTO->getMessage());
            
            $entityManager->persist($contact);
            $entityManager->flush();

            // Envoyer un email de confirmation
            $email = (new TemplatedEmail())
                ->from($this->getParameter('app.mail_from_address'))
                ->to($contact->getEmail())
                ->subject('Confirmation de votre message - Maison Provence')
                ->htmlTemplate('emails/contact_confirmation.html.twig')
                ->context([
                    'contact' => $contact,
                ]);

            $mailer->send($email);

            // Envoyer un email de notification à l'administrateur
            $adminEmail = (new TemplatedEmail())
                ->from($this->getParameter('app.mail_from_address'))
                ->to($this->getParameter('app.mail_from_address'))
                ->subject('Nouveau message de contact - ' . $contact->getSubject())
                ->htmlTemplate('emails/contact_notification.html.twig')
                ->context([
                    'contact' => $contact,
                ]);

            $mailer->send($adminEmail);

            $this->addFlash('success', 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
