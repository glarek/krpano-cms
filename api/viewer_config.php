<?php
return [
    'user' => 'guest',
    'pass' => '$2y$10$EpYa2.3.4.5.6.7.8.9.0.1.2.3.4.5.6.7.8.9.0.1.2.3.4.5' // Default: guest / password (placeholder hash, will be updated by settings)
];
/* 
   Default password is 'guest'.
   Hash: $2y$10$3... (standard bcrypt for 'guest')
   Actually, let's set a real hash for 'guest' properly in settings or just use a simple default.
   
   Hash for 'guest': $2y$10$e.g.
   For now, I'm using the 'password' hash from before:
   $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
*/
?>
<?php
return [
    'user' => 'guest',
    'pass' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' // password: 'password'
];
