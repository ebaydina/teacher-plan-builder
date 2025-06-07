<?php

$version = '1.0.0';
$version = time();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/html" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Teacher Plan Builder</title>
    <meta name="description"
          content="Educational Service: Teacher Plan Builder">
    <meta name="author" content="Lena Baydina">
    <meta name="keywords" content="Alphabet, Calendar, Constructor">

    <meta property="og:title" content="Teacher Plan Builder">
    <meta property="og:type" content="webapp">
    <meta property="og:url" content="https://teacherplanbuilder.com/">
    <meta property="og:description" content="Educational Service: Teacher Plan Builder">
    <meta property="og:image" content="img/avatar.png">

    <link href="/favicon.ico?v=<?= $version ?>" rel="icon" sizes="any">
    <link href="/icon.svg?v=<?= $version ?>" rel="icon" type="image/svg+xml">
    <link href="/apple-touch-icon.png?v=<?= $version ?>" rel="apple-touch-icon">
    <link href="/manifest.webmanifest?v=<?= $version ?>" rel="manifest">

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body>

<header>
    <h1 class="text-3xl font-bold py-4 px-8">
        Teacher Plan Builder
    </h1>
</header>
<nav class="py-4 px-8 m-auto">
    <a href="teacher-plan-builder.php"
       class="outline-4 py-4 px-8 outline-blue-700 rounded-xl"
    >
        Open Teacher Plan Builder Application
    </a>
</nav>

<article class="grid grid-cols-2">
    <div class="flex flex-col">
        <p class="py-4 px-8 text-justify">
            Welcome to
            <a href="www.teacherplanbuilder.com"
               class="underline text-blue-600 hover:text-blue-800 visited:text-purple-600"
            >www.teacherplanbuilder.com</a> -
            <b>a comprehensive lesson-planning tool created
                by a teacher</b>, for teachers,
            tutors, and parents. Designed to simplify your planning
            process, this platform helps you stay organized and focused
            on what matters most: <b>effective instruction</b>.
        </p>
        <p class="py-4 px-8 text-justify">
            With the monthly calendar feature, you can map out daily
            lessons in advance, ensuring a clear and structured
            approach to teaching literacy and other core subjects.
            For younger learners, the Word Constructor tool supports
            early literacy by generating customized name and sight word
            activities tailored to your students’ needs.
        </p>
        <p class="py-4 px-8 text-justify">
            Teacher Plan Builder (TPB) is an innovative, easy-to-use
            platform built to streamline both daily instruction and
            long-term planning for PreK through 3rd grade. Whether you're
            in a classroom, tutoring one-on-one, or guiding
            your child through homeschooling, TPB offers the structure
            and flexibility you need to keep students engaged and
            on track with their learning goals.
        </p>
    </div>
    <div class=" place-items-start">
        <figure class="py-4 px-8 ">
            <img src="img/about/author-20.webp"
                 alt="Lena Baydina"
            >
            <figcaption>The author Lena Baydina</figcaption>
        </figure>
    </div>
</article>

<article class="grid grid-cols-2">
    <div class="place-items-start">
        <figure class="py-4 px-8 ">
            <img src="img/about/children.jpeg"
                 alt="Lena Baydina and children"
            >
        </figure>
    </div>
    <div class="flex flex-col">
        <p class="py-4 px-8 text-justify">
            After over a decade of teaching in both public and
            private schools, I’ve seen the power of what’s possible
            when research-based methods meet creativity. That’s
            exactly what my app and book offer: practical, engaging
            tools for committed educators who want to focus their
            time and energy on the actual work of teaching.
        </p>
        <p class="py-4 px-8 text-justify">
            This tool helps you build a meaningful flow in your
            instruction - while staying aligned with academic goals
            and making thoughtful adjustments to meet the needs of
            your students, whether you're teaching a whole class or
            just one child.
        </p>
        <p class="py-4 px-8 text-justify">
            To the educators, parents, and tutors using this book
            and app—thank you. You play a vital role in shaping
            futures. I hope the resources you find here support
            you as you plan, adapt, and inspire.
        </p>
        <img src="img/about/signature.png" alt="Signature">
        <p class="px-8 text-left">
            With gratitude, <b>Elena Baydina</b>
        </p>
    </div>
</article>

<footer class="py-4 px-8">
    <p class="text-center">Elena&nbsp;Baydina©2025
        <a href="mailto:by.elena@yahoo.com"
           class="underline text-blue-600 hover:text-blue-800 visited:text-purple-600"
        >
            by.elena@yahoo.com
        </a>
    </p>
</footer>

</body>
</html>
