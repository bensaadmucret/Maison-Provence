<?php

namespace App\Form;

use App\Entity\Media;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imageFile', VichImageType::class, [
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Supprimer l\'image',
                'download_uri' => false,
                'image_uri' => true,
                'asset_helper' => true,
                'label' => false,
                'imagine_pattern' => 'product_thumb',
            ])
            ->add('title', TextType::class, [
                'required' => false,
                'label' => 'Titre',
                'attr' => [
                    'placeholder' => 'Titre de l\'image',
                ],
            ])
            ->add('alt', TextType::class, [
                'required' => false,
                'label' => 'Texte alternatif',
                'attr' => [
                    'placeholder' => 'Description de l\'image pour l\'accessibilité',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
        ]);
    }
}
