<?php


namespace App\Form;


use App\Entity\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('xmlFile', FileType::class,[
                'attr' => ['class' => 'custom-file-input form-control-sm'],
                'label_attr' => ['class' => 'custom-file-label'],
                'label' =>'виберіть XML файл',
                'mapped' => false,
            ]);
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => File::class,
        ]);
    }

}