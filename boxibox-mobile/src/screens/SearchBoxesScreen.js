import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
  FlatList,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Picker } from '@react-native-picker/picker';
import siteService from '../services/siteService';
import reservationService from '../services/reservationService';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import Loading from '../components/Loading';
import { COLORS } from '../constants/colors';

const SearchBoxesScreen = ({ navigation }) => {
  const [loading, setLoading] = useState(true);
  const [searching, setSearching] = useState(false);
  const [sites, setSites] = useState([]);
  const [boxes, setBoxes] = useState([]);
  const [searchParams, setSearchParams] = useState({
    site_id: '',
    min_volume: '',
    max_volume: '',
    climate_controlled: false,
    ground_floor: false,
    duration_months: '6',
  });

  useEffect(() => {
    loadSites();
  }, []);

  const loadSites = async () => {
    try {
      const data = await siteService.getSites();
      const sitesList = data.sites || data;
      setSites(sitesList);
      if (sitesList.length > 0) {
        setSearchParams((prev) => ({ ...prev, site_id: sitesList[0].id.toString() }));
      }
    } catch (error) {
      console.error('Error loading sites:', error);
      Alert.alert('Erreur', 'Impossible de charger les sites');
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = async () => {
    if (!searchParams.site_id) {
      Alert.alert('Erreur', 'Veuillez sélectionner un site');
      return;
    }

    setSearching(true);

    try {
      const params = {
        site_id: parseInt(searchParams.site_id),
        duration_months: parseInt(searchParams.duration_months),
      };

      if (searchParams.min_volume) {
        params.min_volume = parseFloat(searchParams.min_volume);
      }
      if (searchParams.max_volume) {
        params.max_volume = parseFloat(searchParams.max_volume);
      }

      const data = await reservationService.searchBoxes(params);
      setBoxes(data.boxes || []);

      if ((data.boxes || []).length === 0) {
        Alert.alert('Aucun résultat', 'Aucun box disponible avec ces critères');
      }
    } catch (error) {
      console.error('Error searching boxes:', error);
      Alert.alert('Erreur', 'Impossible de rechercher les boxes');
    } finally {
      setSearching(false);
    }
  };

  const renderBox = ({ item }) => (
    <TouchableOpacity
      onPress={() =>
        navigation.navigate('BoxDetails', { box: item, searchParams })
      }
    >
      <Card>
        <View style={styles.boxHeader}>
          <Text style={styles.boxNumber}>Box {item.number}</Text>
          <Text style={styles.boxPrice}>
            {item.pricing?.monthly_price_ht?.toFixed(2)} € HT/mois
          </Text>
        </View>

        <View style={styles.boxDetails}>
          <Text style={styles.boxInfo}>Volume: {item.volume} m³</Text>
          <Text style={styles.boxInfo}>Surface: {item.surface} m²</Text>
          <Text style={styles.boxInfo}>Dimensions: {item.dimensions}</Text>
        </View>

        {item.site && (
          <Text style={styles.siteName}>{item.site.name}</Text>
        )}

        <View style={styles.features}>
          {item.features?.climate_controlled && (
            <View style={styles.featureBadge}>
              <Text style={styles.featureText}>❄️ Climatisé</Text>
            </View>
          )}
          {item.features?.ground_floor && (
            <View style={styles.featureBadge}>
              <Text style={styles.featureText}>🏢 RDC</Text>
            </View>
          )}
          {item.features?.vehicle_access && (
            <View style={styles.featureBadge}>
              <Text style={styles.featureText}>🚗 Accès véhicule</Text>
            </View>
          )}
          {item.features?.has_electricity && (
            <View style={styles.featureBadge}>
              <Text style={styles.featureText}>⚡ Électricité</Text>
            </View>
          )}
        </View>

        {item.pricing && (
          <View style={styles.pricing}>
            <Text style={styles.pricingLabel}>Premier paiement</Text>
            <Text style={styles.pricingValue}>
              {item.pricing.first_payment?.toFixed(2)} €
            </Text>
          </View>
        )}
      </Card>
    </TouchableOpacity>
  );

  if (loading) {
    return <Loading />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardView}
      >
        <ScrollView
          style={styles.scrollView}
          keyboardShouldPersistTaps="handled"
        >
          <Card>
            <Text style={styles.sectionTitle}>Critères de recherche</Text>

            <Text style={styles.label}>Site</Text>
            <View style={styles.pickerContainer}>
              <Picker
                selectedValue={searchParams.site_id}
                onValueChange={(value) =>
                  setSearchParams({ ...searchParams, site_id: value })
                }
                style={styles.picker}
              >
                {sites.map((site) => (
                  <Picker.Item
                    key={site.id}
                    label={`${site.name} - ${site.city}`}
                    value={site.id.toString()}
                  />
                ))}
              </Picker>
            </View>

            <Input
              label="Volume minimum (m³)"
              value={searchParams.min_volume}
              onChangeText={(value) =>
                setSearchParams({ ...searchParams, min_volume: value })
              }
              placeholder="Ex: 5"
              keyboardType="numeric"
            />

            <Input
              label="Volume maximum (m³)"
              value={searchParams.max_volume}
              onChangeText={(value) =>
                setSearchParams({ ...searchParams, max_volume: value })
              }
              placeholder="Ex: 15"
              keyboardType="numeric"
            />

            <Text style={styles.label}>Durée (mois)</Text>
            <View style={styles.pickerContainer}>
              <Picker
                selectedValue={searchParams.duration_months}
                onValueChange={(value) =>
                  setSearchParams({ ...searchParams, duration_months: value })
                }
                style={styles.picker}
              >
                <Picker.Item label="1 mois" value="1" />
                <Picker.Item label="3 mois" value="3" />
                <Picker.Item label="6 mois" value="6" />
                <Picker.Item label="12 mois" value="12" />
                <Picker.Item label="24 mois" value="24" />
              </Picker>
            </View>

            <Button
              title="Rechercher"
              onPress={handleSearch}
              loading={searching}
            />
          </Card>

          {boxes.length > 0 && (
            <View style={styles.results}>
              <Text style={styles.resultsTitle}>
                {boxes.length} box{boxes.length > 1 ? 'es' : ''} disponible{boxes.length > 1 ? 's' : ''}
              </Text>
              <FlatList
                data={boxes}
                renderItem={renderBox}
                keyExtractor={(item) => item.id.toString()}
                scrollEnabled={false}
              />
            </View>
          )}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  keyboardView: {
    flex: 1,
  },
  scrollView: {
    flex: 1,
    padding: 16,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 16,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.text.primary,
    marginBottom: 8,
    marginTop: 8,
  },
  pickerContainer: {
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 8,
    backgroundColor: COLORS.white,
    marginBottom: 16,
  },
  picker: {
    height: 50,
  },
  results: {
    marginTop: 16,
  },
  resultsTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 12,
  },
  boxHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  boxNumber: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.text.primary,
  },
  boxPrice: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.primary,
  },
  boxDetails: {
    marginBottom: 8,
  },
  boxInfo: {
    fontSize: 14,
    color: COLORS.text.secondary,
    marginBottom: 4,
  },
  siteName: {
    fontSize: 14,
    color: COLORS.text.secondary,
    marginBottom: 12,
  },
  features: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginBottom: 12,
  },
  featureBadge: {
    backgroundColor: COLORS.primary + '20',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
    marginRight: 8,
    marginBottom: 8,
  },
  featureText: {
    fontSize: 12,
    color: COLORS.primary,
  },
  pricing: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: COLORS.divider,
    paddingTop: 12,
  },
  pricingLabel: {
    fontSize: 14,
    color: COLORS.text.secondary,
  },
  pricingValue: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.success,
  },
});

export default SearchBoxesScreen;
