## STUDY BUDDY 🙂

Welcome to Study Buddy! 

### Installation Instructions - [Local Deployment]

Clone the project repository
```
git clone https://github.com/PaulaGit1/Study-buddy.git
```

Install project dependencies
```
composer update
```

Copy the project environment variables
```
copy .env.example .env
```

Generate Project Key
```
php artisan key:generate
```

Link storage folder with the public folder
```
php artisan storage:link
```

Run migrations to populate the database
```
php artisan migrate
```

Run the seeders to populate the tables with seeded data
```
php artisan db:seed
```

Run the queue worker
```
php artisan queue:work
```

Run project locally 
```
php artisan serve
```


### Developer Instructions

Kindly create your own dev branch and pull updated code base from the main branch e.g.

```
git checkout -m <branch name>
```
```
git pull origin main
```

Then always push changes to your dev branch and initiate a pull request

### Security Vulnerabilities
If you discover a security vulnerability within this application, please send an e-mail to Paula Njenga via[paula.njenga@strathmore.edu](mailto:paula.njenga@strathmore). All security vulnerabilities will be promptly addressed.

### License

The Stuy Buddy  project is open-sourced software licensed under the [Apache license]( http://www.apache.org/licenses/).
