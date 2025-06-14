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

    <link rel="stylesheet" href="css/about-page.css?v=<?= $version ?>"/>
</head>
<body>
<div class="w-full h-full flex flex-col items-center"
     style="background-image: url('img/cover-invisible-background.png');"
>
    <main class="px-8 sm:px-16 flex flex-col items-center bg-white">

        <header class="py-4 min-w-3xs max-w-xs sm:max-w-md md:max-w-xl">
            <h1 class="text-5xl font-bold text-center"> Teacher Plan Builder </h1>
        </header>

        <article class="flex flex-col items-center min-w-3xs max-w-xs sm:max-w-md md:max-w-xl">
            <figure class="py-4">
                <img src="img/about/children.jpeg"
                     alt="Lena Baydina and children"
                >
            </figure>
            <nav class="py-4">
                <a href="teacher-plan-builder.php"
                   target="_blank"
                   class="py-2 outline-4 outline-offset-8  outline-blue-700 rounded-xl"
                >
                    Go to the App
                </a>
            </nav>
            <p class="py-4 text-justify min-w-xs"
            >
                Welcome to
                <a href="https://www.teacherplanbuilder.com"
                   target="_blank"
                   class="underline text-blue-600 hover:text-blue-800 visited:text-purple-600"
                >www.teacherplanbuilder.com</a> -
                <b>a comprehensive lesson-planning tool created
                    by a teacher</b>, for teachers,
                tutors, and parents. Designed to simplify your planning
                process, this platform helps you stay organized and focused
                on what matters most: <b>effective instruction</b>.
            </p>
            <p class="py-4  text-justify"
            >
                With the monthly calendar feature, you can map out daily
                lessons in advance, ensuring a clear and structured
                approach to teaching literacy and other core subjects.
                For younger learners, the Word Constructor tool supports
                early literacy by generating customized name and sight word
                activities tailored to your students’ needs.
            </p>
            <p class="py-4  text-justify"
            >
                Teacher Plan Builder (TPB) is an innovative, easy-to-use
                platform built to streamline both daily instruction and
                long-term planning for PreK through 3rd grade. Whether you're
                in a classroom, tutoring one-on-one, or guiding
                your child through homeschooling, TPB offers the structure
                and flexibility you need to keep students engaged and
                on track with their learning goals.
            </p>
        </article>

        <article class="flex flex-col items-center
        min-w-3xs max-w-xs sm:max-w-md md:max-w-xl">
            <figure class="py-4  ">
                <img src="img/about/author-20.webp"
                     alt="Lena Baydina"
                >
                <figcaption
                >Elena Baydina, the creator of TPB
                </figcaption>
            </figure>
            <p class="py-4  text-justify italic"
            >
                After over a decade of teaching in both public and
                private schools, I’ve seen the power of what’s possible
                when research-based methods meet creativity. That’s
                exactly what my app and book offer: practical, engaging
                tools for committed educators who want to focus their
                time and energy on the actual work of teaching.
            </p>
            <p class="py-4  text-justify italic"
            >
                This tool helps you build a meaningful flow in your
                instruction - while staying aligned with academic goals
                and making thoughtful adjustments to meet the needs of
                your students, whether you're teaching a whole class or
                just one child.
            </p>
            <p class="py-4  text-justify italic"
            >
                To the educators, parents, and tutors using this book
                and app—thank you. You play a vital role in shaping
                futures. I hope the resources you find here support
                you as you plan, adapt, and inspire.
            </p>
            <div class="w-3xs self-end">
                <img src="img/about/signature.png" alt="Signature">
            </div>
            <p class=" text-left"
            >
                With gratitude, <b>Elena Baydina</b>
            </p>
        </article>

        <article class="flex flex-col items-center  min-w-3xs max-w-xs sm:max-w-md md:max-w-xl">
            <div class="shop-item py-4  text-justify"
            >
                <h2 class="text-3xl font-bold text-center">
                    Young Reader Textbook (Prek-1) Elena Baydina
                </h2>
                <div class="shop-img">
                    <img src="img/shop/book/cover1.jpg" alt="book cover">
                </div>
                <p>
                    Rooted in structured literacy principles and
                    phonics-based instruction, this book
                    offers:
                </p>
                <ul>
                    <li>
                        ✔ Step-by-step lessons to support foundational
                        reading and writing development.
                    </li>
                    <li>
                        ✔ Beautifully illustrated resources
                        to engage young learners.
                    </li>
                    <li>
                        ✔ Hands-on, adaptable activities for diverse
                        learning needs, including ELL
                        students.
                    </li>
                    <li>
                        ✔ Proven strategies to close literacy gaps
                        and build confident, capable readers.
                    </li>
                </ul>
            </div>
            <div class="shop-item py-4  text-justify"
            >
                <h2 class="text-3xl font-bold text-center">
                    Teacher Plan Builder App
                </h2>
                <div class="shop-img">
                    <img src="img/shop/app/TPB%20-%20app%20example.png" alt="calendar">
                </div>
                <ul>
                    <li>
                        ✔ Comprehensive Lesson & Unit Planning –
                        Organize instruction through a
                        concept-based framework adaptable for various
                        grade levels.
                    </li>
                    <li>
                        ✔ Extensive Literacy Resource Library –
                        Access phonics lessons, reading
                        strategies, and structured literacy activities
                        to support diverse learners,
                        including ELL students.
                    </li>
                    <li>
                        ✔ Comes with the writing Name Constructor –
                        Create personalized writing
                        exercises to reinforce early literacy skills.
                    </li>
                    <li>
                        ✔ Concept-Based Learning Tools – Build
                        interdisciplinary units that connect
                        literacy with broader themes and
                        inquiry-based learning.
                    </li>
                    <li>
                        ✔ Daily Instruction Calendar – Generate a
                        month-long instructional roadmap to
                        guide daily lessons and student learning,
                        ensuring consistency and progress.
                    </li>
                    <li>
                        ✔ Adaptable for Homeschooling & Traditional
                        Classrooms – Customizable resources
                        for different instructional needs.
                    </li>
                </ul>
            </div>
            <div class="shop-item py-4  text-justify"
            >
                <h2 class="text-3xl font-bold text-center">Name Constructor</h2>
                <div class="shop-img">
                    <img src="img/shop/name-constructor/name-example.webp" alt="name-example">
                </div>
                <p>
                    The Name Constructor is an interactive tool
                    designed to help students practice writing
                    words they are learning, including sight words,
                    their own names, and uppercase/lowercase letters.
                </p>
                <ul>
                    <li>
                        ✔ Personalized Name Practice: Generates
                        worksheets for students to practice
                        writing their own names in both uppercase and
                        lowercase letters.
                    </li>
                    <li>
                        ✔ Sight Word Integration: Allows teachers to
                        input custom word lists for
                        structured practice.
                    </li>
                    <li>
                        ✔ Adaptive Formatting: Provides dashed-line
                        tracing, guided writing, and freehand spaces
                        to support different skill levels.
                    </li>
                    <li>
                        ✔ Printable & Digital Use: Worksheets can be
                        printed for handwriting practice or
                        used on tablets with a stylus.
                    </li>
                </ul>
            </div>
        </article>

        <footer class="py-4 px-16 min-w-3xs max-w-xs sm:max-w-md md:max-w-xl">
            <p class="text-center"
            >Elena&nbsp;Baydina©2025
                <a href="mailto:teacherplanbuilder@gmail.com"
                   class="underline text-blue-600 hover:text-blue-800 visited:text-purple-600"
                >
                    teacherplanbuilder@gmail.com
                </a>
            </p>
        </footer>
    </main>
</div>

</body>
</html>
