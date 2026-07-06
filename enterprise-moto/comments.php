<?php if ( post_password_required() ) return; ?>

<div id="comments">

  <?php if ( have_comments() ) : ?>
    <h2 class="comments-title">
      <?php
      $count = get_comments_number();
      printf(
        esc_html( _n( '%s comentario', '%s comentarios', $count, 'enterprise-moto' ) ),
        '<span>' . number_format_i18n( $count ) . '</span>'
      );
      ?>
    </h2>

    <ol class="comment-list" style="list-style:none;padding:0;">
      <?php
      wp_list_comments( array(
        'style'       => 'ol',
        'short_ping'  => true,
        'avatar_size' => 40,
        'callback'    => 'enterprise_comment_template',
      ) );
      ?>
    </ol>

    <?php the_comments_pagination( array(
      'prev_text' => '← ' . esc_html__( 'Anteriores', 'enterprise-moto' ),
      'next_text' => esc_html__( 'Siguientes', 'enterprise-moto' ) . ' →',
    ) ); ?>

  <?php endif; ?>

  <?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
    <p style="color:var(--mid);font-size:14px;margin-top:24px;">
      <?php esc_html_e( 'Los comentarios están cerrados.', 'enterprise-moto' ); ?>
    </p>
  <?php endif; ?>

  <?php
  comment_form( array(
    'title_reply'          => esc_html__( 'Deja un comentario', 'enterprise-moto' ),
    'title_reply_to'       => esc_html__( 'Responder a %s', 'enterprise-moto' ),
    'cancel_reply_link'    => esc_html__( 'Cancelar respuesta', 'enterprise-moto' ),
    'label_submit'         => esc_html__( 'Publicar comentario', 'enterprise-moto' ),
    'class_submit'         => 'btn btn--dark',
    'comment_notes_before' => '',
    'comment_field'        => '<div class="comment-form-field"><label for="comment">' . esc_html__( 'Comentario', 'enterprise-moto' ) . ' <span class="required">*</span></label><textarea id="comment" name="comment" rows="5" required></textarea></div>',
    'fields' => array(
      'author' => '<div class="comment-form-field"><label for="author">' . esc_html__( 'Nombre', 'enterprise-moto' ) . ' <span class="required">*</span></label><input id="author" name="author" type="text" required autocomplete="name"></div>',
      'email'  => '<div class="comment-form-field"><label for="email">' . esc_html__( 'Email', 'enterprise-moto' ) . ' <span class="required">*</span></label><input id="email" name="email" type="email" required autocomplete="email"></div>',
      'url'    => '',
      'cookies'=> '<div class="comment-form-field" style="display:flex;gap:10px;align-items:flex-start;"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"><label for="wp-comment-cookies-consent" style="font-size:13px;color:var(--mid);font-weight:400;">' . esc_html__( 'Guardar mi nombre y email para la próxima vez que comente.', 'enterprise-moto' ) . '</label></div>',
    ),
  ) );
  ?>

</div><!-- /comments -->

<?php
function enterprise_comment_template( $comment, $args, $depth ) {
  $GLOBALS['comment'] = $comment;
  ?>
  <li <?php comment_class( 'comment' ); ?> id="comment-<?php comment_ID(); ?>">
    <div class="comment-meta">
      <?php echo get_avatar( $comment, 40, '', '', array( 'class' => 'comment-avatar', 'style' => 'border-radius:50%;flex-shrink:0;' ) ); ?>
      <div>
        <span class="comment-author"><?php comment_author(); ?></span>
        <span class="comment-date"><?php comment_date( 'd M Y' ); ?></span>
      </div>
    </div>
    <div class="comment-content">
      <?php if ( '0' == $comment->comment_approved ) : ?>
        <p style="color:var(--mid);font-style:italic;font-size:13px;"><?php esc_html_e( 'Tu comentario está pendiente de moderación.', 'enterprise-moto' ); ?></p>
      <?php endif; ?>
      <?php comment_text(); ?>
    </div>
    <div class="reply">
      <?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'], 'reply_text' => esc_html__( 'Responder', 'enterprise-moto' ) ) ) ); ?>
    </div>
  <?php
}
?>
