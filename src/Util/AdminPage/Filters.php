<?php

namespace App\Util\AdminPage;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class Filters
{

    public function __construct(
        protected string $nomListSession = 'liste',
        protected readonly string $defaultInput = "",
    )
    {
    }

    protected function initListPage(Request $request, ?array $listeInitiale):array{
        $liste = $request->getSession()->get($this->nomListSession);
        if(!$liste){
            $request->getSession()->set($this->nomListSession, $listeInitiale);
            $liste = $listeInitiale ?? [];
        }
        return $liste;
    }

    protected function initInputFilter(Request $request, string $name) : string {
        $filtre = $request->getSession()->get($name);
        if(!$filtre){
            $request->getSession()->set($name, $this->defaultInput);
            $filtre = $this->defaultInput;
        }
        return $filtre;
    }

}
