<?php

namespace App\Controller\Admin;

use App\Entity\Prodotto;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProdottoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Prodotto::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextareaField::new('description'),
            MoneyField::new('price')->setCurrency('EUR'),
            BooleanField::new('avaiable'),
            ImageField::new('image')
                ->setBasePath('/uploads/images') // Dove verrà mostrata
                ->setUploadDir('public/uploads/images') // Dove viene salvata
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]') // nome file auto
                ->setRequired($pageName === 'new'), // obbligatorio solo in creazione
            DateTimeField::new('created_at'),
            DateTimeField::new('update_date'),
        ];
    }
}
