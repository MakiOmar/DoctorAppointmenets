<?php
/**
 * Add Arabic Translations for Existing Diagnoses
 * 
 * This script adds Arabic translations for existing English diagnosis entries
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function snks_add_arabic_diagnoses() {
	global $wpdb;
	
	// Start transaction
	$wpdb->query( 'START TRANSACTION' );
	
	try {
		$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
		
		// Get all existing diagnoses
		$existing_diagnoses = $wpdb->get_results( "SELECT * FROM {$diagnoses_table}" );
		
		// Common mental health diagnoses with Arabic translations
		$diagnosis_translations = array(
			// Anxiety Disorders
			'Anxiety Disorder' => array(
				'ar' => 'اضطراب القلق',
				'description_en' => 'A mental health disorder characterized by feelings of worry, anxiety, or fear that are strong enough to interfere with daily activities.',
				'description_ar' => 'اضطراب صحي نفسي يتميز بمشاعر القلق والخوف التي تكون قوية بما يكفي للتدخل في الأنشطة اليومية.'
			),
			'Generalized Anxiety Disorder' => array(
				'ar' => 'اضطراب القلق العام',
				'description_en' => 'Excessive anxiety and worry about various aspects of life, occurring more days than not for at least 6 months.',
				'description_ar' => 'قلق واهتمام مفرط حول جوانب مختلفة من الحياة، يحدث في معظم الأيام لمدة 6 أشهر على الأقل.'
			),
			'Panic Disorder' => array(
				'ar' => 'اضطراب الهلع',
				'description_en' => 'Recurrent unexpected panic attacks followed by persistent concern about having additional attacks.',
				'description_ar' => 'نوبات هلع متكررة وغير متوقعة يتبعها قلق مستمر حول حدوث نوبات إضافية.'
			),
			'Social Anxiety Disorder' => array(
				'ar' => 'اضطراب القلق الاجتماعي',
				'description_en' => 'Intense fear of social situations and being judged or embarrassed in front of others.',
				'description_ar' => 'خوف شديد من المواقف الاجتماعية والخوف من الحكم أو الإحراج أمام الآخرين.'
			),
			
			// Mood Disorders
			'Depression' => array(
				'ar' => 'الاكتئاب',
				'description_en' => 'A mood disorder that causes persistent feelings of sadness and loss of interest in activities.',
				'description_ar' => 'اضطراب مزاجي يسبب مشاعر حزن مستمرة وفقدان الاهتمام بالأنشطة.'
			),
			'Major Depressive Disorder' => array(
				'ar' => 'الاضطراب الاكتئابي الجسيم',
				'description_en' => 'A serious mental health condition characterized by persistent low mood and loss of interest in activities.',
				'description_ar' => 'حالة صحية نفسية خطيرة تتميز بمزاج منخفض مستمر وفقدان الاهتمام بالأنشطة.'
			),
			'Bipolar Disorder' => array(
				'ar' => 'الاضطراب ثنائي القطب',
				'description_en' => 'A mental health condition characterized by extreme mood swings including emotional highs and lows.',
				'description_ar' => 'حالة صحية نفسية تتميز بتقلبات مزاجية شديدة تشمل ارتفاعات وانخفاضات عاطفية.'
			),
			'Persistent Depressive Disorder' => array(
				'ar' => 'الاضطراب الاكتئابي المستمر',
				'description_en' => 'A long-term form of depression that lasts for at least two years.',
				'description_ar' => 'شكل طويل الأمد من الاكتئاب يستمر لمدة عامين على الأقل.'
			),
			
			// Trauma and Stress Disorders
			'Post-Traumatic Stress Disorder' => array(
				'ar' => 'اضطراب ما بعد الصدمة',
				'description_en' => 'A mental health condition triggered by experiencing or witnessing a terrifying event.',
				'description_ar' => 'حالة صحية نفسية تنجم عن تجربة أو مشاهدة حدث مرعب.'
			),
			'Acute Stress Disorder' => array(
				'ar' => 'اضطراب الإجهاد الحاد',
				'description_en' => 'A short-term mental health condition that can occur within the first month of exposure to a traumatic event.',
				'description_ar' => 'حالة صحية نفسية قصيرة المدى يمكن أن تحدث خلال الشهر الأول من التعرض لحدث صادم.'
			),
			
			// Obsessive-Compulsive and Related Disorders
			'Obsessive-Compulsive Disorder' => array(
				'ar' => 'اضطراب الوسواس القهري',
				'description_en' => 'A mental health condition characterized by unwanted recurring thoughts and behaviors.',
				'description_ar' => 'حالة صحية نفسية تتميز بأفكار وسلوكيات متكررة وغير مرغوب فيها.'
			),
			
			// Eating Disorders
			'Anorexia Nervosa' => array(
				'ar' => 'فقدان الشهية العصبي',
				'description_en' => 'An eating disorder characterized by an intense fear of gaining weight and a distorted body image.',
				'description_ar' => 'اضطراب في الأكل يتميز بخوف شديد من زيادة الوزن وصورة مشوهة للجسم.'
			),
			'Bulimia Nervosa' => array(
				'ar' => 'الشره العصبي',
				'description_en' => 'An eating disorder characterized by binge eating followed by compensatory behaviors.',
				'description_ar' => 'اضطراب في الأكل يتميز بنوبات الأكل الشره يتبعها سلوكيات تعويضية.'
			),
			'Binge Eating Disorder' => array(
				'ar' => 'اضطراب الأكل الشره',
				'description_en' => 'An eating disorder characterized by recurrent episodes of eating large amounts of food.',
				'description_ar' => 'اضطراب في الأكل يتميز بنوبات متكررة من تناول كميات كبيرة من الطعام.'
			),
			
			// Personality Disorders
			'Borderline Personality Disorder' => array(
				'ar' => 'اضطراب الشخصية الحدية',
				'description_en' => 'A mental health condition characterized by unstable moods, behavior, and relationships.',
				'description_ar' => 'حالة صحية نفسية تتميز بتقلبات مزاجية وسلوكية وعلاقات غير مستقرة.'
			),
			'Narcissistic Personality Disorder' => array(
				'ar' => 'اضطراب الشخصية النرجسية',
				'description_en' => 'A mental health condition characterized by an inflated sense of self-importance and a deep need for admiration.',
				'description_ar' => 'حالة صحية نفسية تتميز بإحساس مبالغ فيه بأهمية الذات وحاجة عميقة للإعجاب.'
			),
			
			// Substance Use Disorders
			'Substance Use Disorder' => array(
				'ar' => 'اضطراب استخدام المواد',
				'description_en' => 'A mental health condition characterized by the continued use of substances despite harmful consequences.',
				'description_ar' => 'حالة صحية نفسية تتميز بالاستمرار في استخدام المواد رغم العواقب الضارة.'
			),
			'Alcohol Use Disorder' => array(
				'ar' => 'اضطراب استخدام الكحول',
				'description_en' => 'A pattern of alcohol use that involves problems controlling drinking and continued use despite problems.',
				'description_ar' => 'نمط من استخدام الكحول يتضمن مشاكل في التحكم في الشرب والاستمرار في الاستخدام رغم المشاكل.'
			),
			
			// Sleep Disorders
			'Insomnia' => array(
				'ar' => 'الأرق',
				'description_en' => 'A sleep disorder characterized by difficulty falling asleep, staying asleep, or getting quality sleep.',
				'description_ar' => 'اضطراب في النوم يتميز بصعوبة في النوم أو البقاء نائماً أو الحصول على نوم جيد.'
			),
			'Sleep Apnea' => array(
				'ar' => 'انقطاع النفس أثناء النوم',
				'description_en' => 'A serious sleep disorder where breathing repeatedly stops and starts during sleep.',
				'description_ar' => 'اضطراب خطير في النوم حيث يتوقف التنفس ويبدأ بشكل متكرر أثناء النوم.'
			),
			
			// Other Common Conditions
			'Stress' => array(
				'ar' => 'التوتر',
				'description_en' => 'A state of mental or emotional strain resulting from demanding circumstances.',
				'description_ar' => 'حالة من الإجهاد العقلي أو العاطفي ناتجة عن ظروف متطلبة.'
			),
			'Burnout' => array(
				'ar' => 'الإرهاق المهني',
				'description_en' => 'A state of emotional, physical, and mental exhaustion caused by excessive and prolonged stress.',
				'description_ar' => 'حالة من الإرهاق العاطفي والجسدي والعقلي ناتجة عن التوتر المفرط والمطول.'
			),
			'Grief' => array(
				'ar' => 'الحزن',
				'description_en' => 'A natural response to loss, particularly the loss of someone or something important.',
				'description_ar' => 'استجابة طبيعية للخسارة، خاصة فقدان شخص أو شيء مهم.'
			),
			'Low Self-Esteem' => array(
				'ar' => 'انخفاض تقدير الذات',
				'description_en' => 'A negative evaluation of oneself and one\'s abilities.',
				'description_ar' => 'تقييم سلبي للذات وقدراتها.'
			),
			'Relationship Issues' => array(
				'ar' => 'مشاكل العلاقات',
				'description_en' => 'Difficulties in interpersonal relationships that cause emotional distress.',
				'description_ar' => 'صعوبات في العلاقات الشخصية تسبب ضائقة عاطفية.'
			),
			'Work-Related Stress' => array(
				'ar' => 'التوتر المرتبط بالعمل',
				'description_en' => 'Stress and anxiety related to work environment, responsibilities, or job performance.',
				'description_ar' => 'التوتر والقلق المرتبط ببيئة العمل أو المسؤوليات أو الأداء الوظيفي.'
			),
			'Parenting Stress' => array(
				'ar' => 'توتر الأبوة والأمومة',
				'description_en' => 'Stress and anxiety related to parenting responsibilities and challenges.',
				'description_ar' => 'التوتر والقلق المرتبط بمسؤوليات وتحديات الأبوة والأمومة.'
			),
			'Academic Stress' => array(
				'ar' => 'التوتر الأكاديمي',
				'description_en' => 'Stress and anxiety related to academic performance, exams, and educational responsibilities.',
				'description_ar' => 'التوتر والقلق المرتبط بالأداء الأكاديمي والامتحانات والمسؤوليات التعليمية.'
			),
			'Financial Stress' => array(
				'ar' => 'التوتر المالي',
				'description_en' => 'Stress and anxiety related to financial concerns, debt, or economic insecurity.',
				'description_ar' => 'التوتر والقلق المرتبط بالاهتمامات المالية أو الديون أو عدم الأمان الاقتصادي.'
			),
			'Health Anxiety' => array(
				'ar' => 'قلق الصحة',
				'description_en' => 'Excessive worry about health and fear of having a serious medical condition.',
				'description_ar' => 'قلق مفرط حول الصحة والخوف من الإصابة بحالة طبية خطيرة.'
			),
			'Phobias' => array(
				'ar' => 'الرهاب',
				'description_en' => 'Intense, irrational fear of specific objects, situations, or activities.',
				'description_ar' => 'خوف شديد وغير عقلاني من أشياء أو مواقف أو أنشطة محددة.'
			),
			'Adjustment Disorder' => array(
				'ar' => 'اضطراب التكيف',
				'description_en' => 'An emotional or behavioral reaction to a stressful event or change in a person\'s life.',
				'description_ar' => 'رد فعل عاطفي أو سلوكي لحدث مرهق أو تغيير في حياة الشخص.'
			)
		);
		
		$updated_count = 0;
		$added_count = 0;
		
		// Update existing diagnoses with Arabic translations
		foreach ( $existing_diagnoses as $diagnosis ) {
			$english_name = $diagnosis->name_en ?: $diagnosis->name;
			
			if ( isset( $diagnosis_translations[$english_name] ) ) {
				$translation = $diagnosis_translations[$english_name];
				
				$wpdb->update(
					$diagnoses_table,
					array(
						'name_ar' => $translation['ar'],
						'description_ar' => $translation['description_ar']
					),
					array( 'id' => $diagnosis->id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
				
				$updated_count++;
			}
		}
		
		// Add new diagnoses that don't exist yet
		foreach ( $diagnosis_translations as $english_name => $translation ) {
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$diagnoses_table} WHERE name_en = %s OR name = %s",
				$english_name,
				$english_name
			) );
			
			if ( ! $exists ) {
				$wpdb->insert(
					$diagnoses_table,
					array(
						'name_en' => $english_name,
						'name_ar' => $translation['ar'],
						'description_en' => $translation['description_en'],
						'description_ar' => $translation['description_ar']
					),
					array( '%s', '%s', '%s', '%s' )
				);
				
				$added_count++;
			}
		}
		
		// Commit transaction
		$wpdb->query( 'COMMIT' );
		
		return array(
			'success' => true,
			'message' => "Arabic translations added successfully! Updated: {$updated_count} diagnoses, Added: {$added_count} new diagnoses.",
			'updated' => $updated_count,
			'added' => $added_count
		);
		
	} catch ( Exception $e ) {
		// Rollback transaction
		$wpdb->query( 'ROLLBACK' );
		
		return array(
			'success' => false,
			'message' => 'Failed to add Arabic translations: ' . $e->getMessage()
		);
	}
}

// Note: Menu registration is now handled in ai-admin-enhanced.php

function snks_arabic_diagnoses_page() {
	// Debug: Check if function is being called
	error_log('snks_arabic_diagnoses_page function called');
	
	// Load admin styles
	if ( function_exists( 'snks_load_ai_admin_styles' ) ) {
		snks_load_ai_admin_styles();
	}
	
	// Handle form submission
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'add_arabic_diagnoses' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'add_arabic_diagnoses' ) ) {
			$result = snks_add_arabic_diagnoses();
			
			if ( $result['success'] ) {
				echo '<div class="notice notice-success"><p>' . esc_html( $result['message'] ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( $result['message'] ) . '</p></div>';
			}
		}
	}
	
	// Get current diagnoses count
	global $wpdb;
	$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
	$total_diagnoses = $wpdb->get_var( "SELECT COUNT(*) FROM {$diagnoses_table}" );
	$arabic_diagnoses = $wpdb->get_var( "SELECT COUNT(*) FROM {$diagnoses_table} WHERE name_ar IS NOT NULL AND name_ar != ''" );
	?>
	<div class="wrap">
		<h1>Add Arabic Diagnoses</h1>
		
		<div class="card">
			<h2>Current Status</h2>
			<p><strong>Total Diagnoses:</strong> <?php echo esc_html( $total_diagnoses ); ?></p>
			<p><strong>Diagnoses with Arabic:</strong> <?php echo esc_html( $arabic_diagnoses ); ?></p>
			<p><strong>Diagnoses needing Arabic:</strong> <?php echo esc_html( $total_diagnoses - $arabic_diagnoses ); ?></p>
		</div>
		
		<div class="card">
			<h2>Add Arabic Translations</h2>
			<p>This will add Arabic translations for common mental health diagnoses. It will:</p>
			<ul>
				<li>Update existing diagnoses with Arabic translations</li>
				<li>Add new common diagnoses that don't exist yet</li>
				<li>Preserve all existing data</li>
			</ul>
			
			<form method="post">
				<?php wp_nonce_field( 'add_arabic_diagnoses' ); ?>
				<input type="hidden" name="action" value="add_arabic_diagnoses">
				
				<p>
					<button type="submit" class="button button-primary" onclick="return confirm('Are you sure you want to add Arabic translations? This will update existing diagnoses and add new ones.')">
						Add Arabic Translations
					</button>
				</p>
			</form>
		</div>
		
		<div class="card">
			<h2>Diagnoses That Will Be Added/Updated</h2>
			<p>The following diagnoses will be processed:</p>
			<ul>
				<li><strong>Anxiety Disorders:</strong> Generalized Anxiety, Panic Disorder, Social Anxiety</li>
				<li><strong>Mood Disorders:</strong> Depression, Bipolar Disorder, Persistent Depressive Disorder</li>
				<li><strong>Trauma Disorders:</strong> PTSD, Acute Stress Disorder</li>
				<li><strong>Other Conditions:</strong> OCD, Eating Disorders, Personality Disorders</li>
				<li><strong>Common Issues:</strong> Stress, Burnout, Relationship Issues, Work Stress</li>
			</ul>
		</div>
	</div>
	<?php
} 