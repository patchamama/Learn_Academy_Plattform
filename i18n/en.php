<?php

return [
    // Navigation
    'nav.dashboard'       => 'Dashboard',
    'nav.courses'         => 'Courses',
    'nav.profile'         => 'Profile',
    'nav.settings'        => 'Settings',
    'nav.support'         => 'Support',
    'nav.logout'          => 'Log out',
    'nav.login'           => 'Log in',
    'nav.register'        => 'Register',

    // Auth
    'auth.email'          => 'Email address',
    'auth.password'       => 'Password',
    'auth.name'           => 'Full name',
    'auth.login'          => 'Log in',
    'auth.register'       => 'Create account',
    'auth.forgot'         => 'Forgot password?',
    'auth.logout'         => 'Log out',

    // Auth errors
    'auth.error.fields_required'    => 'All fields are required.',
    'auth.error.invalid_credentials' => 'Invalid email or password.',
    'auth.error.invalid_email'      => 'Please enter a valid email address.',
    'auth.error.password_too_short' => 'Password must be at least 8 characters.',
    'auth.error.email_taken'        => 'This email address is already registered.',
    'auth.error.generic'            => 'An error occurred. Please try again.',

    // Courses
    'course.continue'     => 'Continue learning',
    'course.start'        => 'Start course',
    'course.locked'       => 'Locked',
    'course.unlock'       => 'Unlock this course',
    'course.lessons'      => ':count lessons',
    'course.hours'        => ':count hours of video',
    'course.instructor'   => 'Instructor',
    'course.about'        => 'About this course',
    'course.content'      => 'Course content',
    'course.sections'     => ':count sections',
    'course.completed'    => ':percent% completed',
    'course.sources'      => 'Sources & Attachments',
    'course.search'       => 'Search lessons...',

    // Lesson player
    'lesson.complete'     => 'Complete & continue',
    'lesson.prev'         => 'Previous',
    'lesson.next'         => 'Next',
    'lesson.subtitles'    => 'Subtitles',
    'lesson.speed'        => 'Playback speed',
    'lesson.back'         => 'Back to dashboard',

    // Comments
    'comment.title'       => 'Comments',
    'comment.placeholder' => 'Write a comment...',
    'comment.submit'      => 'Post comment',
    'comment.reply'       => 'Reply',
    'comment.pending'     => 'Pending moderation',
    'comment.approved'    => 'Approved',
    'comment.load_more'   => 'Load more comments',

    // Settings
    'settings.title'      => 'Settings',
    'settings.theme'      => 'Theme',
    'settings.theme_light' => 'Light',
    'settings.theme_dark' => 'Dark',
    'settings.language'   => 'Language',
    'settings.font_size'  => 'Font size',
    'settings.subtitles'  => 'Show subtitles by default',
    'settings.speed'      => 'Default playback speed',
    'settings.save'       => 'Save settings',
    'settings.saved'      => 'Settings saved',

    // Dashboard
    'dashboard.greeting'  => 'Hello, :name!',
    'dashboard.progress'  => 'Your progress',
    'dashboard.continue'  => 'Continue watching',
    'dashboard.enrolled'  => 'My courses',
    'dashboard.no_courses' => 'You are not enrolled in any courses yet.',
    'dashboard.expires'   => 'Access expires: :date',

    // Admin
    'admin.users'         => 'Users',
    'admin.grant_access'  => 'Grant access',
    'admin.revoke_access' => 'Revoke access',
    'admin.moderation'    => 'Comment moderation',
    'admin.approve'       => 'Approve',
    'admin.reject'        => 'Reject',
    'admin.payments'      => 'Payments',
    'admin.pending'       => ':count pending',

    // Payments
    'payment.title'       => 'Get access',
    'payment.stripe'      => 'Pay with card',
    'payment.paypal'      => 'Pay with PayPal',
    'payment.success'     => 'Payment successful! You now have access to :course.',
    'payment.failed'      => 'Payment failed. Please try again.',
    'payment.access_for'  => 'Access for 1 year',

    // Access / enrollment
    'access.locked_title'   => 'This lesson is locked',
    'access.locked_message' => 'Purchase the course or ask an admin to grant you access.',
    'access.expired_title'  => 'Your access has expired',
    'access.expired_message' => 'Renew your access to continue learning.',
    'access.renew'          => 'Renew access',

    // General
    'general.save'        => 'Save',
    'general.cancel'      => 'Cancel',
    'general.delete'      => 'Delete',
    'general.edit'        => 'Edit',
    'general.back'        => 'Back',
    'general.loading'     => 'Loading...',
    'general.error'       => 'An error occurred. Please try again.',
    'general.success'     => 'Done!',
    'general.search'      => 'Search',
];
