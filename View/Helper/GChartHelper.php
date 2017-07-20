<?php

App::uses('AppHelper', 'View/Helper');
/**
 * CakePHP helper that acts as a wrapper for Google's Visualization JS Package.
 */
class GChartHelper extends AppHelper {

	public $helpers = array('Html');

	/**
	* Available visualization types
	*
	* @var array
	*/
	protected $chart_types = array(
		'area' => array(
			'method'=>'AreaChart',
			'package' => 'corechart'
		),
		'bar' => array(
			'method' => 'BarChart',
			'package' => 'corechart'
		),
		'pie' => array(
			'method' => 'PieChart',
			'package' => 'corechart'
		),
		'line' => array(
			'method' => 'LineChart',
			'package' => 'corechart'
		),
		'table' => array(
			'method' => 'Table',
			'package' => 'table'
		),
		'geochart' => array(
			'method' => 'GeoChart',
			'package' => 'geochart'
		)
	);

	protected $packages_loaded = array();

	/**
	 * Default options
	 *
	 * @var array
	 */
	protected $defaults = array(
		'title' => '',
		'type' => 'area',
		'width' => 600,
		'height' => 300,
		'is3D' => 'true',
		'legend' => 'bottom'
	);

	/**
	 * Creates a div tag meant to be filled with the Google visualization.
	 *
	 * @param string $name
	 * @param array $options
	 * @return string Div tag output
	 */
	public function start($name, $options=array()) {
		$options = array_merge(array('id' => $name), $options);
		return $this->Html->tag('div', '', $options);
	}
	
	
	public function visualize($name, $data=array()) {
		$out = '';
		switch($data['type']) {
			case "line":
				$out = $this->visualizeLineChart($name, $data);
				break;
			case "pie":
				$out = $this->visualizePieChart($name, $data);
				break;
		}
		return $out;
	}

	/**
	 * Returns javascript that will create the visualization requested.
	 *
	 * @param string $name
	 * @param array $data
	 * @return string
	 */
	public function visualizeLineChart($name, $data=array()) {
		$data = array_merge($this->defaults, $data);

		$o = $this->loadPackage($data['type']);
		$drawTemplate = '
<script type="text/javascript">
$(document).ready(function () {
	function drawChart%s () {
		var line_data = new google.visualization.DataTable();
		%s
		%s
		line_chart.draw(line_data, {
			width: %s,
			height: %s,
			is3D: %s,
			legend: "%s",
			title: "%s"
		});
	}
	
	drawChart%s();
});
</script>
		';
		$o .= sprintf($drawTemplate, 
			$name,
			$this->loadDataAndLabelsLine($data), 
			$this->instantiateGraph($name, $data['type']), 
			$data['width'],
			$data['height'],
			$data['is3D'],
			$data['legend'],
			$data['title'],
			$name
		);
		return trim($o);
   }

	/**
	 * Returns javascript that will create the visualization requested.
	 *
	 * @param string $name
	 * @param array $data
	 * @return string
	 */
	public function visualizePieChart($name, $data=array()) {
		$data = array_merge($this->defaults, $data);

		$o = $this->loadPackage($data['type']);
		$drawTemplate = '
<script type="text/javascript">
$(document).ready(function () {
	function drawChart%s () {
		var pie_data = new google.visualization.arrayToDataTable(%s);
		%s
		pie_chart.draw(pie_data, {
			width: %s,
			height: %s,
			is3D: %s,
			legend: "%s",
			title: "%s"
		});
	}
	
	drawChart%s();
});
</script>
		';
		$o .= sprintf($drawTemplate, 
			$name,
			$this->loadDataAndLabelsPie($data), 
			$this->instantiateGraph($name, $data['type']), 
			$data['width'],
			$data['height'],
			$data['is3D'],
			$data['legend'],
			$data['title'],
			$name
		);
		return trim($o);
   }

	/**
	 * Returns json representation of labels and data for the visualization constructor.
	 *
	 * @param array $data
	 * @return string
	 */
	protected function loadDataAndLabelsLine($data) {
		$out = array();
		
		foreach ($data['labels'] as $labels) {
			$out[] = __("%s_data.addColumn('%s', '%s');", $data['type'], $labels['type'], $labels['label']);
		}
		
		$out[] = __('%s_data.addRows(%s);', $data['type'], json_encode($data['data']));
		
		return implode("\n", $out);
	}

	/**
	 * Returns json representation of labels and data for the visualization constructor.
	 *
	 * @param array $data
	 * @return string
	 */
	protected function loadDataAndLabelsPie($data) {
		$out = array();
		
		foreach ($data['labels'] as $labels) {
			$out[] = __("%s_data.addColumn('%s', '%s');", $data['type'], $labels['type'], $labels['label']);
		}
		
		$out[] = __('%s_data.addRows(%s);', $data['type'], json_encode($data['data']));
		
		return implode("\n", $out);
	}

	/**
	 * Loads the specific visualization package.  Will only load a package once.
	 *
	 * @param string $type
	 * @return string
	 */
	protected function loadPackage($type) {
		if (!empty($this->packages_loaded[$this->chart_types[$type]['package']])) {
			return '';
		}
		$packageTemplate = '
<script type="text/javascript">
$(document).ready(function () {
	google.load("visualization", "1", {packages: ["%s"]});
});
</script>
		';
		$this->packages_loaded[$this->chart_types[$type]['package']] = true;
		return sprintf(trim($packageTemplate), $this->chart_types[$type]['package']);
	}

	/**
	 * Returns javascript to instantiate the Google visualization package.
	 *
	 * @param string $name
	 * @param string $type
	 * @return string
	 */
	protected function instantiateGraph($name, $type='area') {
		$graphInitTemplate = '
var '.$type.'_chart = new google.visualization.%s(document.getElementById("%s"));
		';
		return sprintf(trim($graphInitTemplate), $this->chart_types[$type]['method'], $name);
	}
}
