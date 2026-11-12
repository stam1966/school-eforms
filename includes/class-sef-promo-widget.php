<?php
/**
 * Έτοιμο widget «Ηλεκτρονικά έντυπα (School e-Forms)» (0.9.2): ο σύνδεσμος-προβολή [school_eforms_link]
 * για sidebar/υποσέλιδο, με τις επιλογές του σε κουτάκια — χωρίς να γράψει κανείς shortcode.
 */
defined( 'ABSPATH' ) || exit;

class SEF_Promo_Widget extends WP_Widget {

	private static $defaults = array(
		'kicker'   => 'Νέα υπηρεσία του σχολείου',
		'title'    => 'Ηλεκτρονικά έντυπα και αιτήσεις',
		'text'     => 'Αιτήσεις και δηλώσεις γονέων, άδειες εκπαιδευτικών και υπεύθυνες δηλώσεις, από το σπίτι.',
		'button'   => 'Είσοδος',
		'sections' => '',
		'season'   => '',
	);

	public function __construct() {
		parent::__construct(
			'sef_promo',
			'Ηλεκτρονικά έντυπα (School e-Forms)',
			array(
				'description'                 => 'Κάρτα με κουμπί «Είσοδος» προς τη σελίδα των ηλεκτρονικών εντύπων, στα χρώματα του θέματος.',
				'customize_selective_refresh' => true,
			)
		);
	}

	public function widget( $args, $instance ) {
		$i = wp_parse_args( (array) $instance, self::$defaults );
		$html = SEF_Promo::shortcode(
			array(
				'kicker'   => $i['kicker'],
				'title'    => $i['title'],
				'text'     => $i['text'],
				'button'   => $i['button'],
				'sections' => $i['sections'] ? 'yes' : 'no',
				'season'   => $i['season'] ? 'yes' : 'no',
				'heading'  => 'h3',
			)
		);
		if ( '' === $html ) {
			return;
		}
		echo $args['before_widget'] . $html . $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public function form( $instance ) {
		$i = wp_parse_args( (array) $instance, self::$defaults );
		foreach ( array( 'kicker' => 'Μικρή επικεφαλίδα (κενό = καμία)', 'title' => 'Τίτλος', 'button' => 'Κουμπί' ) as $k => $label ) :
			?>
			<p><label for="<?php echo esc_attr( $this->get_field_id( $k ) ); ?>"><?php echo esc_html( $label ); ?>:</label>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( $k ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $k ) ); ?>" value="<?php echo esc_attr( $i[ $k ] ); ?>"></p>
			<?php
		endforeach;
		?>
		<p><label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>">Κείμενο (κενό = κανένα):</label>
		<textarea class="widefat" rows="3" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>"><?php echo esc_textarea( $i['text'] ); ?></textarea></p>
		<p><input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'sections' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'sections' ) ); ?>" value="1" <?php checked( (bool) $i['sections'] ); ?>>
		<label for="<?php echo esc_attr( $this->get_field_id( 'sections' ) ); ?>">Σύνδεσμοι «Γονείς &amp; μαθητές» / «Εκπαιδευτικοί»</label><br>
		<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'season' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'season' ) ); ?>" value="1" <?php checked( (bool) $i['season'] ); ?>>
		<label for="<?php echo esc_attr( $this->get_field_id( 'season' ) ); ?>">«Αυτή την περίοδο: …» (ανοιχτά εποχικά έντυπα)</label></p>
		<p class="description">Τα χρώματα έρχονται από το θέμα· η σελίδα προορισμού ορίζεται στις Ρυθμίσεις → Ηλεκτρονικά έντυπα → Εμφάνιση.</p>
		<?php
	}

	public function update( $new, $old ) {
		$out = array();
		foreach ( array( 'kicker', 'title', 'button' ) as $k ) {
			$out[ $k ] = isset( $new[ $k ] ) ? mb_substr( sanitize_text_field( $new[ $k ] ), 0, 150 ) : '';
		}
		$out['text']     = isset( $new['text'] ) ? mb_substr( sanitize_textarea_field( $new['text'] ), 0, 300 ) : '';
		$out['sections'] = ! empty( $new['sections'] ) ? '1' : '';
		$out['season']   = ! empty( $new['season'] ) ? '1' : '';
		if ( '' === $out['title'] ) {
			$out['title'] = self::$defaults['title'];
		}
		$out['button'] = SEF_Promo::clean_button( $out['button'] ); // χωρίς βελάκι στο τέλος· κενό → «Είσοδος»
		return $out;
	}
}
