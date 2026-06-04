# Laravel Basics
    - Installation of laravel
    - File Structure Explaination ...


# Guidlines ...
    - Laravel have main functionlaties : Routes  , Models , Controllers , Views ...
        - Views : Frontend
        - Controllers : Backend
        - Models + Migratoins = Database
        - Routes : Web Application Path ..
        - Create Authentication :-
            - composer require laravel/ui
            - php artisan ui vue --auth ( Download NodeJS And Install )
            - npm install & npm run dev
            - php artisan migrate 
# Interview Questions
    - What is view and what is the extension of view .. : view is use for frontend and extension of view in laravel is .blade.php

# Command For Make Controller
    - Php artisan make:controller nameController 
#refernce Links
    - https://livewire.laravel.com/docs/4.x/quickstart
# Product Development Proccess ..
    - # 1: Laravel Installation
    - # 2: Project Plan 
    - # 3: Authentication System
    - # 4: Installation of Livewire
    - # 5: Required Components Create
    - # 6: Routes For All Components
# Database Required Tables + Models ... ( Jitny tables hun gay utni hi migrations hun gi)
    - Categories ( id  , title )
    - Users
    - Products (id, name , descrption , quantity , image , purchase price , sale price , status ,  )
    - Supplier 
    - Sales
# Guidelines :
    - Migrations always plural of model ..